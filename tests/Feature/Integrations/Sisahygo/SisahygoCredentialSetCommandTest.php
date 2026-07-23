<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SisahygoCredentialSetCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn () => 'local');

        config()->set('sisahygo.api.environments.sandbox.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
        config()->set('sisahygo.api.environments.production.base_url', 'https://api.sisahygo.online/api/v1/client');
    }

    public function test_command_is_blocked_outside_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $account = $this->readyAccount();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsOutputToContain('This command is available only in the local environment.')
            ->assertFailed();

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_hidden_key_is_stored_through_existing_service_without_printing_plaintext(): void
    {
        $account = $this->readyAccount(['code' => 'CMD-READY']);
        $apiKey = 'local-secret-key-123';

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsQuestion('Sisahygo API key', $apiKey)
            ->expectsOutputToContain('Sisahygo credential stored securely.')
            ->expectsOutputToContain('Fingerprint: '.SisahygoApiCredential::fingerprint($apiKey))
            ->doesntExpectOutputToContain($apiKey)
            ->assertSuccessful();

        $credential = SisahygoApiCredential::query()->firstOrFail();

        $this->assertSame(SisahygoApiEnvironment::Sandbox, $credential->environment);
        $this->assertSame(SisahygoCredentialStatus::Active, $credential->status);
        $this->assertSame($apiKey, app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Sandbox)->apiKey());

        $raw = DB::table('sisahygo_api_credentials')->where('id', $credential->id)->value('encrypted_api_key');
        $this->assertStringNotContainsString($apiKey, $raw);

        $activityMetadata = DB::table('client_account_activity_logs')->pluck('metadata')->implode(' ');
        $this->assertStringNotContainsString($apiKey, $activityMetadata);
    }

    public function test_invalid_client_account_is_rejected(): void
    {
        $this->artisan('sisahygo:credential:set', ['--account' => 999999])
            ->expectsOutputToContain('Client Account was not found.')
            ->assertFailed();

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_inactive_client_account_is_rejected(): void
    {
        $account = $this->readyAccount(['status' => ClientAccountStatus::Suspended]);

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsOutputToContain('Client Account must be active before provisioning a credential.')
            ->assertFailed();

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_empty_key_is_rejected(): void
    {
        $account = $this->readyAccount();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsQuestion('Sisahygo API key', '')
            ->expectsOutputToContain('API key cannot be empty.')
            ->assertFailed();

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_existing_active_credential_requires_confirmation_before_replacement(): void
    {
        $account = $this->readyAccount(['code' => 'CMD-ROTATE']);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Existing', 'old-key');

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsConfirmation('Replace active sandbox credential for CMD-ROTATE?', 'no')
            ->expectsOutputToContain('No credential was changed.')
            ->assertFailed();

        $this->assertSame('old-key', app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Sandbox)->apiKey());
    }

    public function test_confirmed_replacement_uses_existing_rotation_lifecycle(): void
    {
        $account = $this->readyAccount(['code' => 'CMD-ROTATE-YES']);
        $old = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Existing', 'old-key');

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsConfirmation('Replace active sandbox credential for CMD-ROTATE-YES?', 'yes')
            ->expectsQuestion('Sisahygo API key', 'new-key')
            ->assertSuccessful();

        $new = app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Sandbox);

        $this->assertSame(SisahygoCredentialStatus::Revoked, $old->fresh()->status);
        $this->assertSame($old->id, $new->rotated_from_id);
        $this->assertSame('new-key', $new->apiKey());
    }

    public function test_sandbox_and_production_credentials_remain_separated(): void
    {
        $account = $this->readyAccount();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id, '--environment' => 'sandbox'])
            ->expectsQuestion('Sisahygo API key', 'sandbox-key')
            ->assertSuccessful();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id, '--environment' => 'production'])
            ->expectsQuestion('Sisahygo API key', 'production-key')
            ->assertSuccessful();

        $this->assertSame('sandbox-key', app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Sandbox)->apiKey());
        $this->assertSame('production-key', app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Production)->apiKey());
    }

    public function test_readiness_is_reported_without_modifying_customer_links_or_capabilities(): void
    {
        $account = $this->readyAccount(sender: false, includeHistory: false);
        $linkCount = ClientAccountCustomer::query()->count();
        $capabilityCount = ClientAccountCapability::query()->count();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsOutputToContain('Readiness: shipment.view present.')
            ->expectsOutputToContain('Readiness: shipment.history is not enabled.')
            ->expectsOutputToContain('Readiness: no authorized sender relationship found for GET /receivers.')
            ->expectsQuestion('Sisahygo API key', 'readiness-key')
            ->assertSuccessful();

        $this->assertSame($linkCount, ClientAccountCustomer::query()->count());
        $this->assertSame($capabilityCount, ClientAccountCapability::query()->count());
    }

    public function test_account_without_active_customer_links_is_rejected(): void
    {
        $account = ClientAccount::factory()->active()->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsOutputToContain('Client Account must have at least one active customer link.')
            ->assertFailed();
    }

    public function test_account_missing_shipment_view_capability_is_rejected(): void
    {
        $account = ClientAccount::factory()->active()->create();
        ClientAccountCustomer::factory()->for($account)->senderAndReceiver()->create(['customer_id' => 10001]);

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id])
            ->expectsOutputToContain('Client Account is missing required capability: shipment.view')
            ->assertFailed();
    }

    public function test_production_environment_rejects_sandbox_host(): void
    {
        $account = $this->readyAccount();
        config()->set('sisahygo.api.environment', 'production');
        config()->set('sisahygo.api.environments.production.base_url', 'https://sandbox-api.example.test/api/v1/client');

        $this->artisan('sisahygo:credential:set', ['--account' => $account->id, '--environment' => 'production'])
            ->expectsOutputToContain('production environment cannot use a sandbox API host')
            ->assertFailed();
    }

    private function readyAccount(array $attributes = [], bool $sender = true, bool $includeHistory = true): ClientAccount
    {
        $account = ClientAccount::factory()->active()->create($attributes);

        ClientAccountCustomer::factory()
            ->for($account)
            ->state(['can_send' => $sender, 'can_receive' => true, 'customer_id' => 10001])
            ->create();

        ClientAccountCapability::factory()
            ->for($account)
            ->capability(ClientCapability::ShipmentView)
            ->create();

        if ($includeHistory) {
            ClientAccountCapability::factory()
                ->for($account)
                ->capability(ClientCapability::ShipmentHistory)
                ->create();
        }

        return $account;
    }
}
