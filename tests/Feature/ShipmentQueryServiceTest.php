<?php

namespace Tests\Feature;

use App\Application\Shipment\ShipmentQueryService;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShipmentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_uses_core_supported_filters_only(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => Http::response($this->fixture('shipments-index-success.json'))]);

        $result = app(ShipmentQueryService::class)->list($user, $account, [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-17',
            'status' => 'created',
            'keyword' => '12345',
            'sender_customer_ids' => [10001],
            'receiver_customer_ids' => [20001],
            'page' => 2,
            'per_page' => 15,
        ]);

        $this->assertSame('SH10001', $result['items'][0]['tracking_no']);
        $this->assertSame('สร้างรายการแล้ว', $result['items'][0]['order_status_label']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'from_date=2026-07-01')
            && str_contains($request->url(), 'to_date=2026-07-17')
            && str_contains($request->url(), 'order_status=created')
            && str_contains($request->url(), 'tracking_no=12345')
            && str_contains($request->url(), 'page=2')
            && ! str_contains($request->url(), 'sender_customer_ids')
            && ! str_contains($request->url(), 'receiver_customer_ids'));
    }

    public function test_non_numeric_keyword_maps_to_order_header_no(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => Http::response($this->fixture('shipments-index-success.json'))]);

        app(ShipmentQueryService::class)->list($user, $account, ['keyword' => 'OH10001']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'order_header_no=OH10001')
            && ! str_contains($request->url(), 'tracking_no='));
    }

    public function test_detail_uses_shipment_view_capability_and_maps_timeline(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments/SH10001' => Http::response($this->fixture('shipment-detail-success.json'))]);

        $shipment = app(ShipmentQueryService::class)->detail($user, $account, 'SH10001');

        $this->assertSame('SH10001', $shipment['summary']['tracking_no']);
        $this->assertSame('Fake parcel', $shipment['items'][0]['product_name']);
        $this->assertSame('รับสินค้าแล้ว', $shipment['timeline'][0]['label']);
    }

    public function test_detail_formats_timezone_aware_timeline_in_app_timezone_without_double_shift(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments/SH-TZ' => Http::response([
            'data' => [
                'tracking_no' => 'SH-TZ',
                'order_header_no' => 'OH-TZ',
                'history' => [
                    ['status' => 'picked_up', 'changed_at' => '2026-09-03T07:36:00Z'],
                    ['status' => 'loaded', 'changed_at' => '2026-09-04T18:30:00Z'],
                    ['status' => 'delivered', 'changed_at' => '2026-09-05T09:42:00+07:00'],
                    ['status' => 'created', 'changed_at' => '2026-09-05 09:42:00'],
                ],
            ],
        ])]);

        $shipment = app(ShipmentQueryService::class)->detail($user, $account, 'SH-TZ');

        $this->assertSame('03/09/2026 14:36', $shipment['timeline'][0]['occurred_at_display']);
        $this->assertSame('05/09/2026 01:30', $shipment['timeline'][1]['occurred_at_display']);
        $this->assertSame('05/09/2026 09:42', $shipment['timeline'][2]['occurred_at_display']);
        $this->assertSame('05/09/2026 09:42', $shipment['timeline'][3]['occurred_at_display']);
    }

    public function test_suspended_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(ClientAccountStatus::Suspended);

        $this->expectException(AuthorizationException::class);

        app(ShipmentQueryService::class)->list($user, $account);
    }

    public function test_archived_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(ClientAccountStatus::Archived);

        $this->expectException(AuthorizationException::class);

        app(ShipmentQueryService::class)->list($user, $account);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(ClientAccountStatus $status = ClientAccountStatus::Active): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['status' => $status]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
