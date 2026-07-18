<?php

namespace Tests\Feature;

use App\Application\Dashboard\GetCustomerDashboard;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_bounded_requests_and_core_meta_totals(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount(withOrderCreate: true);
        $this->fakeDashboardResponses();

        $result = app(GetCustomerDashboard::class)($user, $account);

        $this->assertSame(4, $result['request_count']);
        $this->assertSame(7, $result['summary_cards'][0]['value']);
        $this->assertFalse($result['summary_cards'][1]['available']);
        $this->assertNull($result['summary_cards'][1]['value']);
        $this->assertSame(11, $result['summary_cards'][2]['value']);
        $this->assertSame(2, $result['summary_cards'][3]['value']);
        $this->assertSame('OH10001', $result['latest_shipments'][0]['order_header_no']);
        $this->assertSame('OH90001', $result['attention_shipments'][0]['order_header_no']);
        $this->assertSame('บริษัท รับสินค้าไทย จำกัด', $result['recent_receivers'][0]['name']);
        $this->assertSame('น้ำดื่ม 600 ml', $result['recent_products'][0]['product_name']);
        $this->assertTrue($result['can_create_order']);

        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'from_date=2026-07-17')
            && str_contains($request->url(), 'to_date=2026-07-17')
            && str_contains($request->url(), 'per_page=1'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'order_status=completed')
            && str_contains($request->url(), 'per_page=1'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'order_status=problem')
            && str_contains($request->url(), 'per_page=5'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/shipments/10001'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'receiver_customer_id')
            || str_contains($request->url(), 'client_reference_no'));
    }

    public function test_active_client_account_uses_selected_credential_context(): void
    {
        [$user, $account] = $this->eligibleAccount(apiKey: 'selected-secret');
        $this->fakeDashboardResponses();

        app(GetCustomerDashboard::class)($user, $account);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
    }

    public function test_shipment_view_capability_is_required(): void
    {
        [$user, $account] = $this->eligibleAccount(withShipmentView: false, withOrderCreate: true);
        $this->fakeDashboardResponses();

        $this->expectException(AuthorizationException::class);

        app(GetCustomerDashboard::class)($user, $account);
    }

    public function test_suspended_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(status: ClientAccountStatus::Suspended);

        $this->expectException(AuthorizationException::class);

        app(GetCustomerDashboard::class)($user, $account);
    }

    public function test_archived_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(status: ClientAccountStatus::Archived);

        $this->expectException(AuthorizationException::class);

        app(GetCustomerDashboard::class)($user, $account);
    }

    public function test_order_create_capability_only_controls_shortcut_state(): void
    {
        [$user, $account] = $this->eligibleAccount(withOrderCreate: false);
        $this->fakeDashboardResponses();

        $result = app(GetCustomerDashboard::class)($user, $account);

        $this->assertFalse($result['can_create_order']);
        Http::assertSentCount(4);
    }

    private function fakeDashboardResponses(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['order_status'] ?? null) === 'completed') {
                return Http::response($this->shipmentResponse([], 11, 1));
            }

            if (($query['order_status'] ?? null) === 'problem') {
                return Http::response($this->shipmentResponse([$this->shipment(90001, 'OH90001', 'problem')], 2, 5));
            }

            if (($query['from_date'] ?? null) === '2026-07-17' && ($query['to_date'] ?? null) === '2026-07-17') {
                return Http::response($this->shipmentResponse([], 7, 1));
            }

            return Http::response($this->shipmentResponse([
                $this->shipment(10001, 'OH10001', 'delivered'),
                $this->shipment(10002, 'OH10002', 'created'),
            ], 30, 5));
        }]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(
        ClientAccountStatus $status = ClientAccountStatus::Active,
        bool $withShipmentView = true,
        bool $withOrderCreate = false,
        string $apiKey = 'secret-api-key',
    ): array {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['status' => $status]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        if ($withShipmentView) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        }
        if ($withOrderCreate) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        }
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return [$user, $account];
    }

    /** @param array<int, array<string, mixed>> $data */
    private function shipmentResponse(array $data, int $total, int $perPage): array
    {
        return [
            'data' => $data,
            'meta' => ['current_page' => 1, 'per_page' => $perPage, 'total' => $total, 'last_page' => 1],
        ];
    }

    /** @return array<string, mixed> */
    private function shipment(int $id, string $orderHeaderNo, string $status): array
    {
        return [
            'id' => $id,
            'tracking_no' => (string) $id,
            'client_reference_no' => 'REF-'.$id,
            'order_header_no' => $orderHeaderNo,
            'order_header_date' => '2026-07-16',
            'order_status' => $status,
            'branch_rec' => 'เชียงใหม่',
            'customer_rec' => 'บริษัท รับสินค้าไทย จำกัด',
            'items' => [
                ['product_id' => 6639, 'product_name' => 'น้ำดื่ม 600 ml', 'unit_id' => 1, 'unit' => 'ขวด', 'amount' => 2],
            ],
        ];
    }
}
