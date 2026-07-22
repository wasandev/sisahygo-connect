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
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingItemData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingOrderData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingRequestData;
use App\Integrations\Sisahygo\V1\Endpoints\OrderCheckingsEndpoint;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BulkOrderCheckingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_post_sends_exact_verified_shape_and_omits_ui_fields(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-success.json'), 201)]);

        $result = app(OrderCheckingsEndpoint::class)->createBulk($context, $this->requestData());

        $this->assertSame('all_succeeded', $result->outcome());
        $this->assertSame(2, $result->summary->success);
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk'
                && $payload['batch_reference_no'] === 'BATCH-20260721'
                && $payload['batch_date'] === '2026-07-21'
                && $payload['orders'][0]['client_reference_no'] === 'BC-20260721-001'
                && $payload['orders'][0]['items'][0]['amount'] === '2.5000'
                && ! array_key_exists('row_key', $payload['orders'][0])
                && ! array_key_exists('selected_receiver', $payload['orders'][0])
                && ! array_key_exists('customer_id', $payload['orders'][0]);
        });
    }

    public function test_bulk_207_maps_as_processed_partial_success_without_exposing_core_id(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-partial.json'), 207)]);

        $result = app(OrderCheckingsEndpoint::class)->createBulk($context, $this->requestData())->toSafeArray();

        $this->assertSame('partial_success', $result['outcome']);
        $this->assertSame(1, $result['summary']['failed']);
        $this->assertSame('corr-row-002', $result['results'][1]['correlation_id']);
        $this->assertArrayNotHasKey('id', $result['results'][0]);
    }

    public function test_bulk_422_uses_existing_validation_exception(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-bulk-validation-error.json'), 422)]);

        try {
            app(SisahygoApiClient::class)->post($context, '/order-checkings/bulk', ['orders' => []]);
            $this->fail('Expected validation exception.');
        } catch (SisahygoValidationException $exception) {
            $this->assertSame('VALIDATION_ERROR', $exception->safeContext()['api_error_code']);
            $this->assertArrayHasKey('orders.1.items.0.amount', $exception->safeContext()['validation_errors']);
        }
    }

    public function test_bulk_post_is_not_blindly_retried(): void
    {
        $context = $this->context();
        Http::fakeSequence()
            ->push($this->fixture('server-error.json'), 500)
            ->push($this->fixture('order-checking-bulk-success.json'), 201);

        $this->expectException(SisahygoServerException::class);

        try {
            app(OrderCheckingsEndpoint::class)->createBulk($context, $this->requestData());
        } finally {
            Http::assertSentCount(1);
        }
    }

    private function requestData(): BulkOrderCheckingRequestData
    {
        return new BulkOrderCheckingRequestData(
            batchReferenceNo: 'BATCH-20260721',
            batchDate: '2026-07-21',
            orders: [
                new BulkOrderCheckingOrderData(
                    clientReferenceNo: 'BC-20260721-001',
                    receiverCustomerId: 20001,
                    remark: 'ส่งก่อนบ่าย',
                    items: [
                        new BulkOrderCheckingItemData(
                            productId: 6639,
                            unitId: 1,
                            amount: '2.5000',
                            remark: 'line test',
                            clientLineId: 'L1',
                            clientItemNo: 'ITEM001',
                            clientProductCode: 'FG001',
                        ),
                    ],
                ),
            ],
        );
    }

    private function context(): SisahygoIntegrationContext
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderBulk)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::OrderBulk);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
