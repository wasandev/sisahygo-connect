<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Livewire\Dashboard\CustomerDashboard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_open_dashboard(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount(withOrderCreate: true);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('หน้าหลัก')
            ->assertSee('Selected Account')
            ->assertSee('OH10001')
            ->assertSee('รายการวันนี้')
            ->assertSee('ยังคำนวณไม่ได้')
            ->assertSee('สร้างรายการส่งสินค้า')
            ->assertSee('ค้นหาทั่ว Workspace')
            ->assertSee('งานที่รอดำเนินการ')
            ->assertSee('การแจ้งเตือนล่าสุด')
            ->assertDontSee('secret-api-key');

        Http::assertSentCount(6);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_order_create_shortcut_is_disabled_without_capability(): void
    {
        [$user, $account] = $this->eligibleAccount(withOrderCreate: false);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ยังไม่มีสิทธิ์สร้างรายการ')
            ->assertDontSee(route('order-checking'));
    }

    public function test_empty_recent_sections_render_safe_states(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'per_page' => 5, 'total' => 0, 'last_page' => 1]])]);

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ยังไม่มีรายการรับส่งสินค้า')
            ->assertSee('เริ่มต้นด้วยการสร้างรายการแรก')
            ->assertSee('ยังไม่มีรายการที่ต้องติดตามในช่วงนี้')
            ->assertSee('ยังไม่มีข้อมูลผู้รับในรายการล่าสุด')
            ->assertSee('ยังไม่มีข้อมูลสินค้าในรายการล่าสุด');
    }

    public function test_refresh_reloads_dashboard_and_loading_state_is_present(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('wire:loading.attr="disabled"', false)
            ->call('refresh')
            ->assertSet('pageError', null)
            ->assertSee('OH10001');

        Http::assertSentCount(12);
    }

    public function test_render_refresh_does_not_call_remote_api_again(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->call('$refresh')
            ->assertSee('OH10001');

        Http::assertSentCount(6);
    }

    public function test_selected_account_stays_stable_during_hydrated_refresh(): void
    {
        $user = User::factory()->create();
        $other = $this->accountFor($user, 'Other Account', 'other-secret');
        $selected = $this->accountFor($user, 'Selected Account', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selected->id]);
        $this->fakeDashboardResponses();
        app()->forgetInstance(ClientAccount::class);

        Livewire::test(CustomerDashboard::class)
            ->call('refresh')
            ->assertSet('pageError', null);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
        $this->assertTrue($other->exists);
    }

    public function test_account_without_shipment_view_shows_safe_authorization_state(): void
    {
        [$user, $account] = $this->eligibleAccount(withShipmentView: false, withOrderCreate: true);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSet('unavailable', true)
            ->assertSee('Client Account นี้ยังไม่มีสิทธิ์ดูข้อมูลหน้าหลัก');
    }

    public function test_safe_connection_error_is_shown_without_secret(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(fn () => throw new ConnectionException('timed out with secret-api-key'));

        Livewire::test(CustomerDashboard::class)
            ->assertSet('pageError', 'ไม่สามารถเชื่อมต่อ Sisahygo ได้ กรุณาลองใหม่อีกครั้ง')
            ->assertDontSee('secret-api-key');
    }

    public function test_malformed_response_shows_safe_error(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response(['meta' => []])]);

        Livewire::test(CustomerDashboard::class)
            ->assertSet('pageError', 'รูปแบบข้อมูลจาก Sisahygo ไม่ตรงตามที่คาดไว้');
    }

    private function fakeDashboardResponses(): void
    {
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']]),
            'https://sandbox-api.sisahygo.online/api/v1/client/payments*' => Http::response($this->fixture('payments-index-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
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
            },
        ]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(bool $withShipmentView = true, bool $withOrderCreate = false): array
    {
        $user = User::factory()->create();
        $account = $this->accountFor($user, 'Selected Account', 'secret-api-key', $withShipmentView, $withOrderCreate);

        return [$user, $account];
    }

    private function accountFor(User $user, string $name, string $apiKey, bool $withShipmentView = true, bool $withOrderCreate = false): ClientAccount
    {
        $account = ClientAccount::factory()->create(['name' => $name]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        if ($withShipmentView) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        }
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::PaymentView)->create();
        if ($withOrderCreate) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        }
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return $account;
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

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
