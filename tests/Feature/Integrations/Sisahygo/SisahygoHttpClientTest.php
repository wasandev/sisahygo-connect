<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use App\Integrations\Sisahygo\Logging\SisahygoApiLogger;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\Endpoints\ReceiversEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ShipmentsEndpoint;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

class SisahygoHttpClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_request_sends_expected_url_headers(): void
    {
        $context = $this->context();
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
        ]);

        $receivers = app(ReceiversEndpoint::class)->list($context);

        $this->assertSame(20001, $receivers[0]->customerId);
        Http::assertSent(function ($request) use ($context) {
            return $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/receivers'
                && $request->hasHeader('X-Api-Key', 'secret-api-key')
                && $request->hasHeader('Accept', 'application/json')
                && str_contains($request->header('Content-Type')[0] ?? '', 'application/json')
                && $request->hasHeader('X-Correlation-ID', $context->correlationId)
                && $request->hasHeader('User-Agent', 'Sisahygo Connect');
        });
    }

    public function test_get_retries_retryable_failures(): void
    {
        $context = $this->context();
        Http::fakeSequence()
            ->push($this->fixture('server-error.json'), 500)
            ->push($this->fixture('receivers-success.json'), 200);

        app(ReceiversEndpoint::class)->list($context);

        Http::assertSentCount(2);
    }

    public function test_post_does_not_retry_blindly(): void
    {
        $context = $this->context();
        Http::fakeSequence()
            ->push($this->fixture('server-error.json'), 500)
            ->push(['data' => ['ok' => true]], 200);

        $this->expectException(SisahygoServerException::class);

        try {
            app(SisahygoApiClient::class)->post($context, '/order-checkings', ['client_reference_no' => 'REF-1']);
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_connection_failure_maps_to_safe_exception(): void
    {
        $context = $this->context();
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->expectException(SisahygoConnectionException::class);

        app(SisahygoApiClient::class)->get($context, '/receivers');
    }

    public function test_successful_response_logs_actual_http_status(): void
    {
        $logs = $this->captureApiLogs();
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('receivers-success.json'), 200)]);

        app(ReceiversEndpoint::class)->list($context);

        $this->assertSame(200, $logs->records[0]['status']);
        $this->assertTrue($logs->records[0]['success']);
    }

    public function test_forbidden_response_logs_actual_http_status(): void
    {
        $logs = $this->captureApiLogs();
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('forbidden.json'), 403)]);

        try {
            app(SisahygoApiClient::class)->get($context, '/receivers');
            $this->fail('Expected SisahygoAuthorizationException was not thrown.');
        } catch (SisahygoAuthorizationException) {
            // Expected path.
        }

        $this->assertSame(403, $logs->records[0]['status']);
        $this->assertFalse($logs->records[0]['success']);
        $this->assertSame(SisahygoAuthorizationException::class, $logs->records[0]['exception']::class);
    }

    public function test_unauthorized_response_logs_actual_http_status(): void
    {
        $logs = $this->captureApiLogs();
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('unauthorized.json'), 401)]);

        try {
            app(SisahygoApiClient::class)->get($context, '/receivers');
            $this->fail('Expected SisahygoAuthenticationException was not thrown.');
        } catch (SisahygoAuthenticationException) {
            // Expected path.
        }

        $this->assertSame(401, $logs->records[0]['status']);
        $this->assertFalse($logs->records[0]['success']);
        $this->assertSame(SisahygoAuthenticationException::class, $logs->records[0]['exception']::class);
    }

    public function test_connection_failure_logs_null_http_status(): void
    {
        $logs = $this->captureApiLogs();
        $context = $this->context();
        Http::fake(fn () => throw new ConnectionException('timed out'));

        try {
            app(SisahygoApiClient::class)->get($context, '/receivers');
            $this->fail('Expected SisahygoConnectionException was not thrown.');
        } catch (SisahygoConnectionException) {
            // Expected path.
        }

        $this->assertNull($logs->records[0]['status']);
        $this->assertFalse($logs->records[0]['success']);
        $this->assertSame(SisahygoConnectionException::class, $logs->records[0]['exception']::class);
    }

    #[DataProvider('errorProvider')]
    public function test_status_errors_map_to_specific_exceptions(string $fixture, int $status, string $exception): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture($fixture), $status)]);

        $this->expectException($exception);

        app(SisahygoApiClient::class)->get($context, '/receivers');
    }

    public static function errorProvider(): array
    {
        return [
            ['unauthorized.json', 401, SisahygoAuthenticationException::class],
            ['forbidden.json', 403, SisahygoAuthorizationException::class],
            ['not-found.json', 404, SisahygoNotFoundException::class],
            ['validation-error.json', 422, SisahygoValidationException::class],
            ['rate-limited.json', 429, SisahygoRateLimitException::class],
            ['server-error.json', 500, SisahygoServerException::class],
        ];
    }

    public function test_malformed_json_maps_to_unexpected_response_exception(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response('not-json', 200, ['Content-Type' => 'application/json'])]);

        $this->expectException(SisahygoUnexpectedResponseException::class);

        app(SisahygoApiClient::class)->get($context, '/receivers');
    }

    public function test_shipments_mapping_uses_enums_and_deduplicates_both_role_results(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('shipments-index-success.json'))]);

        $shipments = app(ShipmentsEndpoint::class)->list($context);

        $this->assertCount(2, $shipments);
        $this->assertSame('SH10001', $shipments[0]->trackingNo);
        $this->assertSame('H', $shipments[0]->paymentType->value);
        $this->assertSame(0, $shipments[0]->paymentStatus->value);
        $this->assertSame(20002, $shipments[1]->receiverCustomerId);
    }

    public function test_shipment_detail_maps_items_and_status_history(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('shipment-detail-success.json'))]);

        $detail = app(ShipmentsEndpoint::class)->detail($context, 'SH10001');

        $this->assertSame('SH10001', $detail->summary->trackingNo);
        $this->assertSame('Fake parcel', $detail->items[0]->name);
        $this->assertSame('picked_up', $detail->statusHistory[0]->status);
    }

    private function captureApiLogs(): object
    {
        $logs = new class
        {
            public array $records = [];
        };

        $this->instance(SisahygoApiLogger::class, new class($logs) extends SisahygoApiLogger
        {
            public function __construct(private readonly object $logs) {}

            public function record(SisahygoIntegrationContext $context, string $endpoint, string $method, ?int $status, int $durationMs, int $retryCount, bool $success, ?Throwable $exception = null, array $extra = []): void
            {
                $this->logs->records[] = [
                    'context' => $context,
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'status' => $status,
                    'duration_ms' => $durationMs,
                    'retry_count' => $retryCount,
                    'success' => $success,
                    'exception' => $exception,
                    'extra' => $extra,
                ];
            }
        });

        return $logs;
    }

    private function context(): SisahygoIntegrationContext
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        ClientAccountCustomer::factory()->for($account)->receiver()->create(['customer_id' => 20001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
