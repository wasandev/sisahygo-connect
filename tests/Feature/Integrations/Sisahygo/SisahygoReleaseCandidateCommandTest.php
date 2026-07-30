<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SisahygoReleaseCandidateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_status_output_is_sanitized(): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        $this->fakeReadOnlyEndpoints();

        $this->artisan('sisahygo:integration-status', ['--account' => $account->id, '--user' => $user->id])
            ->expectsOutputToContain('PASS configuration')
            ->expectsOutputToContain('PASS credential')
            ->expectsOutputToContain('PASS connectivity')
            ->doesntExpectOutputToContain('secret-api-key')
            ->doesntExpectOutputToContain('X-Api-Key')
            ->assertSuccessful();
    }

    #[DataProvider('credentialFailureProvider')]
    public function test_integration_status_handles_unusable_credentials_safely(string $mode): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');

        if ($mode === 'revoked') {
            app(SisahygoApiCredentialService::class)->revoke(SisahygoApiCredential::query()->where('client_account_id', $account->id)->first());
        }

        if ($mode === 'environment_mismatch') {
            config()->set('sisahygo.api.environment', 'production');
        }

        if ($mode === 'invalid') {
            Http::fake(['*' => Http::response($this->fixture('unauthorized.json'), 401)]);
        } else {
            Http::fake();
        }

        $command = $this->artisan('sisahygo:integration-status', ['--account' => $account->id, '--user' => $user->id])
            ->doesntExpectOutputToContain('secret-api-key')
            ->doesntExpectOutputToContain('X-Api-Key')
            ->assertFailed();

        $this->assertNotNull($command);
    }

    public static function credentialFailureProvider(): array
    {
        return [
            ['revoked'],
            ['environment_mismatch'],
            ['invalid'],
        ];
    }

    public function test_read_only_smoke_test_is_default_and_does_not_post(): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        $this->fakeReadOnlyEndpoints();

        $this->artisan('sisahygo:smoke-test', ['--account' => $account->id, '--user' => $user->id])
            ->expectsOutputToContain('PASS configuration')
            ->expectsOutputToContain('PASS connectivity')
            ->expectsOutputToContain('PASS dashboard')
            ->expectsOutputToContain('SKIP write_order_checking')
            ->doesntExpectOutputToContain('secret-api-key')
            ->assertSuccessful();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_write_smoke_test_requires_explicit_confirmation(): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        $this->fakeReadOnlyEndpoints();

        $this->artisan('sisahygo:smoke-test', ['--account' => $account->id, '--user' => $user->id, '--include-write' => true])
            ->expectsOutputToContain('FAIL write_order_checking: write checks require --confirm-write')
            ->assertFailed();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_write_smoke_test_refuses_production(): void
    {
        config()->set('sisahygo.api.environment', 'production');
        [$user, $account] = $this->readyAccount(SisahygoApiEnvironment::Production, 'secret-api-key');
        $this->fakeReadOnlyEndpoints('https://api.sisahygo.online/api/v1/client');

        $this->artisan('sisahygo:smoke-test', [
            '--account' => $account->id,
            '--user' => $user->id,
            '--include-write' => true,
            '--confirm-write' => true,
            '--receiver-id' => 20001,
            '--product-id' => 6639,
            '--unit-id' => 1,
        ])->expectsOutputToContain('FAIL write_order_checking: write smoke test is allowed only in sandbox')
            ->assertFailed();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_write_smoke_test_does_not_retry_post(): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        Http::fake(function ($request) {
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/order-checkings')) {
                return Http::response($this->fixture('server-error.json'), 500);
            }

            return $this->readOnlyResponse($request->url());
        });

        $this->artisan('sisahygo:smoke-test', [
            '--account' => $account->id,
            '--user' => $user->id,
            '--include-write' => true,
            '--confirm-write' => true,
            '--receiver-id' => 20001,
            '--product-id' => 6639,
            '--unit-id' => 1,
        ])->expectsOutputToContain('FAIL write_order_checking')
            ->assertFailed();

        $postCount = collect(Http::recorded())->filter(fn ($record) => $record[0]->method() === 'POST')->count();
        $this->assertSame(1, $postCount);
    }

    public function test_staging_write_smoke_test_requires_confirmation(): void
    {
        config()->set('app.env', 'staging');
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        $this->fakeReadOnlyEndpoints();

        $this->artisan('sisahygo:smoke-test', ['--account' => $account->id, '--user' => $user->id, '--include-write' => true])
            ->expectsOutputToContain('FAIL write_order_checking: write checks require --confirm-write')
            ->doesntExpectOutputToContain('secret-api-key')
            ->assertFailed();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_write_smoke_test_refuses_production_endpoint_even_when_configured_as_sandbox(): void
    {
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.base_url', 'https://api.sisahygo.online/api/v1/client');
        config()->set('sisahygo.api.environments.production.base_url', 'https://api.sisahygo.online/api/v1/client');
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        $this->fakeReadOnlyEndpoints('https://api.sisahygo.online/api/v1/client');

        $this->artisan('sisahygo:smoke-test', [
            '--account' => $account->id,
            '--user' => $user->id,
            '--include-write' => true,
            '--confirm-write' => true,
            '--receiver-id' => 20001,
            '--product-id' => 6639,
            '--unit-id' => 1,
        ])->expectsOutputToContain('FAIL write_order_checking: write smoke test refuses the production API endpoint')
            ->doesntExpectOutputToContain('secret-api-key')
            ->assertFailed();

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_staging_write_smoke_reference_is_recognizable(): void
    {
        config()->set('app.env', 'staging');
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');
        Http::fake(function ($request) {
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/order-checkings')) {
                return Http::response($this->fixture('order-checking-success.json'), 201);
            }

            return $this->readOnlyResponse($request->url());
        });

        $this->artisan('sisahygo:smoke-test', [
            '--account' => $account->id,
            '--user' => $user->id,
            '--include-write' => true,
            '--confirm-write' => true,
            '--receiver-id' => 20001,
            '--product-id' => 6639,
            '--unit-id' => 1,
        ])->expectsOutputToContain('PASS write_order_checking: created client_reference_no STG-SMOKE-')
            ->assertSuccessful();
    }

    public function test_non_production_banner_is_visible_for_staging(): void
    {
        config()->set('app.env', 'staging');
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.release.build', 'rc-1 / unsafe text');

        $html = Blade::render('<x-connect.environment-banner />');

        $this->assertStringContainsString('STAGING', $html);
        $this->assertStringContainsString('Sandbox API', $html);
        $this->assertStringContainsString('Release Candidate', $html);
        $this->assertStringContainsString('sandbox-api.sisahygo.online', $html);
        $this->assertStringContainsString('rc-1unsafetext', $html);
        $this->assertStringNotContainsString('secret-api-key', $html);
        $this->assertStringNotContainsString('X-Api-Key', $html);
    }

    public function test_non_production_banner_is_hidden_for_production(): void
    {
        config()->set('app.env', 'production');
        config()->set('sisahygo.api.environment', 'production');
        config()->set('sisahygo.api.environments.production.base_url', 'https://api.sisahygo.online/api/v1/client');

        $html = Blade::render('<x-connect.environment-banner />');

        $this->assertStringNotContainsString('Release Candidate', $html);
        $this->assertStringNotContainsString('Sandbox API', $html);
    }

    #[DataProvider('smokeFailureProvider')]
    public function test_smoke_test_reports_core_failures_with_failure_exit_code(string $mode): void
    {
        [$user, $account] = $this->readyAccount(apiKey: 'secret-api-key');

        Http::fake(function () use ($mode) {
            return match ($mode) {
                'timeout' => throw new ConnectionException('timed out with secret-api-key'),
                'rate_limit' => Http::response($this->fixture('rate-limited.json'), 429),
                'malformed' => Http::response('not-json', 200, ['Content-Type' => 'application/json']),
            };
        });

        $this->artisan('sisahygo:smoke-test', ['--account' => $account->id, '--user' => $user->id])
            ->doesntExpectOutputToContain('secret-api-key')
            ->assertFailed();
    }

    public static function smokeFailureProvider(): array
    {
        return [
            ['timeout'],
            ['rate_limit'],
            ['malformed'],
        ];
    }

    public function test_diagnostics_output_is_sanitized(): void
    {
        $this->artisan('sisahygo:diagnostics')
            ->expectsOutputToContain('Sisahygo Connect diagnostics')
            ->expectsOutputToContain('php_version:')
            ->expectsOutputToContain('laravel_version:')
            ->doesntExpectOutputToContain('DB_PASSWORD')
            ->doesntExpectOutputToContain('secret-api-key')
            ->doesntExpectOutputToContain('X-Api-Key')
            ->assertSuccessful();
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function readyAccount(SisahygoApiEnvironment $environment = SisahygoApiEnvironment::Sandbox, string $apiKey = 'secret-api-key'): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->active()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCustomer::factory()->for($account)->senderAndReceiver()->create(['customer_id' => 10001]);
        ClientAccountCustomer::factory()->for($account)->receiver()->create(['customer_id' => 20001]);

        foreach ([ClientCapability::SettingsManage, ClientCapability::ShipmentView, ClientCapability::ShipmentHistory, ClientCapability::PaymentView, ClientCapability::OrderCreate] as $capability) {
            ClientAccountCapability::factory()->for($account)->capability($capability)->create();
        }

        app(SisahygoApiCredentialService::class)->create($account, $environment, 'Sandbox', $apiKey);

        return [$user, $account];
    }

    private function fakeReadOnlyEndpoints(string $baseUrl = 'https://sandbox-api.sisahygo.online/api/v1/client'): void
    {
        Http::fake(fn ($request) => $this->readOnlyResponse($request->url(), $baseUrl));
    }

    private function readOnlyResponse(string $url, string $baseUrl = 'https://sandbox-api.sisahygo.online/api/v1/client'): mixed
    {
        if (str_starts_with($url, $baseUrl.'/ping')) {
            return Http::response(['data' => ['status' => 'ok']]);
        }

        if (str_starts_with($url, $baseUrl.'/units')) {
            return Http::response($this->fixture('units-success.json'));
        }

        if (str_starts_with($url, $baseUrl.'/receivers')) {
            return Http::response($this->fixture('receivers-success.json'));
        }

        if (str_starts_with($url, $baseUrl.'/products')) {
            return Http::response($this->fixture('products-success.json'));
        }

        if (str_starts_with($url, $baseUrl.'/payments')) {
            return Http::response($this->fixture('payments-index-success.json'));
        }

        if (str_starts_with($url, $baseUrl.'/shipments')) {
            return Http::response($this->fixture('shipments-index-success.json'));
        }

        return Http::response(['data' => []]);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
