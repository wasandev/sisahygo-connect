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
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingRequest;
use App\Integrations\Sisahygo\V1\Endpoints\OrderCheckingsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ProductsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\UnitsEndpoint;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderCheckingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_and_units_endpoints_use_confirmed_urls_and_headers(): void
    {
        $context = $this->context();
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response($this->fixture('products-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
        ]);

        $products = app(ProductsEndpoint::class)->search($context, search: 'น้ำ');
        $units = app(UnitsEndpoint::class)->list($context);

        $this->assertSame(6639, $products[0]->productId);
        $this->assertSame(1, $units[0]->unitId);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/products?search=%E0%B8%99%E0%B9%89%E0%B8%B3'
            && $request->hasHeader('X-Api-Key', 'secret-api-key'));
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/units'
            && $request->hasHeader('X-Api-Key', 'secret-api-key'));
    }

    public function test_order_checking_post_body_excludes_sender_and_branch_fields_and_maps_201(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-success.json'), 201)]);

        $result = app(OrderCheckingsEndpoint::class)->create($context, new OrderCheckingRequest(
            clientReferenceNo: 'SC-20260716-ABC123',
            receiverCustomerId: 20001,
            remark: 'ส่งก่อนบ่าย',
            items: [[
                'product_id' => 6639,
                'unit_id' => 1,
                'amount' => 2,
                'remark' => '100 แผ่น',
                'client_line_id' => 'L1',
                'client_item_no' => 'ITEM001',
                'client_product_code' => 'FG001',
            ]],
        ));

        $this->assertSame('checking', $result->orderStatus);
        $this->assertSame('SC-20260716-ABC123', $result->clientReferenceNo);
        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings'
                && ! array_key_exists('customer_id', $payload)
                && ! array_key_exists('branch_id', $payload)
                && ! array_key_exists('branch_rec_id', $payload)
                && $payload['customer_rec_id'] === 20001
                && $payload['items'][0]['product_id'] === 6639;
        });
    }

    public function test_standard_validation_error_envelope_is_available_safely(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-validation-error.json'), 422)]);

        try {
            app(SisahygoApiClient::class)->post($context, '/order-checkings', ['items' => []]);
            $this->fail('Expected validation exception.');
        } catch (SisahygoValidationException $exception) {
            $this->assertSame('VALIDATION_ERROR', $exception->safeContext()['api_error_code']);
            $this->assertArrayHasKey('items.0.product_id', $exception->safeContext()['validation_errors']);
            $this->assertArrayNotHasKey('api_key', $exception->safeContext());
        }
    }

    public function test_reconciliation_lookup_uses_client_reference_path(): void
    {
        $context = $this->context();
        Http::fake(['*' => Http::response($this->fixture('order-checking-lookup-success.json'))]);

        $result = app(OrderCheckingsEndpoint::class)->findByClientReference($context, 'SC-20260716-ABC123');

        $this->assertSame(456789, $result->id);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/SC-20260716-ABC123');
    }

    private function context(): SisahygoIntegrationContext
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::OrderCreate);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
