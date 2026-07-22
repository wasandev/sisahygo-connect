<?php

namespace Tests\Feature;

use App\Application\History\ListOrderHistory;
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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HistoryListOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_history_query_uses_last_30_days(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount();
        $this->fakeHistoryList();

        $result = app(ListOrderHistory::class)($user, $account);

        $this->assertSame('2026-06-18', $result['filters']['date_from']);
        $this->assertSame('2026-07-17', $result['filters']['date_to']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'from_date=2026-06-18')
            && str_contains($request->url(), 'to_date=2026-07-17'));
    }

    public function test_explicit_date_filters_are_normalized_correctly(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakeHistoryList();

        $result = app(ListOrderHistory::class)($user, $account, [
            'preset' => ListOrderHistory::PRESET_CUSTOM,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-15',
        ]);

        $this->assertSame('2026-07-01', $result['filters']['date_from']);
        $this->assertSame('2026-07-15', $result['filters']['date_to']);
    }

    public function test_invalid_date_range_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount();

        $this->expectException(ValidationException::class);

        app(ListOrderHistory::class)($user, $account, [
            'preset' => ListOrderHistory::PRESET_CUSTOM,
            'date_from' => '2026-07-17',
            'date_to' => '2026-07-01',
        ]);
    }

    public function test_supported_status_is_forwarded_and_unsupported_filters_are_not_sent(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakeHistoryList();

        app(ListOrderHistory::class)($user, $account, [
            'preset' => ListOrderHistory::PRESET_CUSTOM,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-17',
            'status' => 'delivered',
            'receiver_customer_id' => 20001,
            'client_reference_no' => 'REF-1',
            'product_id' => 6639,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'order_status=delivered')
            && ! str_contains($request->url(), 'receiver_customer_id')
            && ! str_contains($request->url(), 'client_reference_no')
            && ! str_contains($request->url(), 'product_id'));
    }

    public function test_shipment_listing_service_is_reused_for_identifier_filters(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakeHistoryList();

        app(ListOrderHistory::class)($user, $account, ['keyword' => '12345']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/shipments')
            && str_contains($request->url(), 'tracking_no=12345'));
    }

    public function test_active_client_account_uses_correct_credential_context(): void
    {
        [$user, $account] = $this->eligibleAccount(apiKey: 'selected-secret');
        $this->fakeHistoryList();

        app(ListOrderHistory::class)($user, $account);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
    }

    public function test_suspended_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(status: ClientAccountStatus::Suspended);

        $this->expectException(AuthorizationException::class);

        app(ListOrderHistory::class)($user, $account);
    }

    public function test_archived_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(status: ClientAccountStatus::Archived);

        $this->expectException(AuthorizationException::class);

        app(ListOrderHistory::class)($user, $account);
    }

    public function test_recent_receivers_are_grouped_from_visible_results(): void
    {
        $service = app(ListOrderHistory::class);

        $receivers = $service->recentReceivers($this->visibleItems(), 5);

        $this->assertCount(2, $receivers);
        $this->assertSame('บริษัท รับสินค้าไทย จำกัด', $receivers[0]['name']);
        $this->assertSame(2, $receivers[0]['count']);
        $this->assertSame('2026-07-16', $receivers[0]['latest_order_date']);
    }

    public function test_recent_product_summary_uses_list_items_without_detail_requests(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakeHistoryList();

        $result = app(ListOrderHistory::class)($user, $account);

        $this->assertSame('น้ำดื่ม 600 ml', $result['recent_products'][0]['product_name']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/shipments') && ! str_contains($request->url(), '/shipments/SH'));
    }

    public function test_recent_product_limit_is_enforced(): void
    {
        $service = app(ListOrderHistory::class);
        $items = [];

        for ($i = 1; $i <= 7; $i++) {
            $items[] = [
                'order_header_date' => '2026-07-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'items' => [[
                    'product_id' => $i,
                    'product_name' => "สินค้า {$i}",
                    'unit_name' => 'ชิ้น',
                ]],
            ];
        }

        $this->assertCount(5, $service->recentProducts($items, 5));
    }

    /** @return array<int, array<string, mixed>> */
    private function visibleItems(): array
    {
        return [
            ['receiver_customer_id' => null, 'receiver_name' => 'บริษัท รับสินค้าไทย จำกัด', 'order_header_date' => '2026-07-10'],
            ['receiver_customer_id' => null, 'receiver_name' => 'บริษัท รับสินค้าไทย จำกัด', 'order_header_date' => '2026-07-16'],
            ['receiver_customer_id' => null, 'receiver_name' => 'ร้านค้าปลายทาง', 'order_header_date' => '2026-07-11'],
        ];
    }

    private function fakeHistoryList(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => Http::response($this->historyFixture())]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(ClientAccountStatus $status = ClientAccountStatus::Active, string $apiKey = 'secret-api-key'): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['status' => $status]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return [$user, $account];
    }

    /** @return array<string, mixed> */
    private function historyFixture(): array
    {
        return [
            'data' => [
                [
                    'id' => 10001,
                    'tracking_no' => '10001',
                    'client_reference_no' => 'REF-10001',
                    'order_header_no' => 'OH10001',
                    'order_header_date' => '2026-07-16',
                    'order_status' => 'delivered',
                    'branch_rec' => 'เชียงใหม่',
                    'customer_rec' => 'บริษัท รับสินค้าไทย จำกัด',
                    'items' => [
                        ['product_id' => 6639, 'product_name' => 'น้ำดื่ม 600 ml', 'unit_id' => 1, 'unit' => 'ขวด', 'amount' => 2],
                    ],
                ],
                [
                    'id' => 10002,
                    'tracking_no' => '10002',
                    'order_header_no' => 'OH10002',
                    'order_header_date' => '2026-07-15',
                    'order_status' => 'created',
                    'branch_rec' => 'ขอนแก่น',
                    'customer_rec' => 'ร้านค้าปลายทาง',
                    'items' => [
                        ['product_id' => 6639, 'product_name' => 'น้ำดื่ม 600 ml', 'unit_id' => 1, 'unit' => 'ขวด', 'amount' => 1],
                    ],
                ],
            ],
            'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 2, 'last_page' => 1],
        ];
    }
}
