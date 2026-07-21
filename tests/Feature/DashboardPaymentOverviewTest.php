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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPaymentOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        config()->set('sisahygo.dashboard.payment_cache_enabled', true);
        config()->set('sisahygo.dashboard.payment_cache_ttl', 60);
        Cache::store('array')->clear();
    }

    public function test_authenticated_selected_account_sees_payment_widgets_and_recent_payments(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ภาพรวมการชำระเงิน')
            ->assertSee('2,350.50')
            ->assertSee('รายการค้างชำระ')
            ->assertSee('AR-P-1001')
            ->assertSee('วางบิลต้นทาง')
            ->assertSee('วางบิลปลายทาง')
            ->assertSee('เก็บเงินปลายทาง')
            ->assertDontSee('secret-api-key');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/payments')
            && str_contains($request->url(), 'per_page=5'));
    }

    public function test_dashboard_payment_links_open_filtered_payment_center(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee(route('payments'), false)
            ->assertSee(route('payments', ['payment_status' => 'outstanding']), false)
            ->assertSee(route('payments', ['payment_status' => 'paid']), false);
    }

    public function test_payment_api_failure_does_not_break_dashboard_or_show_zero_summary(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses(paymentStatus: 500);

        Livewire::test(CustomerDashboard::class)
            ->assertSee('Selected Account')
            ->assertSee('OH10001')
            ->assertSee('ยังไม่สามารถโหลดข้อมูลการชำระเงินได้ในขณะนี้')
            ->assertDontSee('มูลค่ารวม</p>\n                                <p class="mt-2 text-2xl font-bold text-connect-navy-900">0', false);
    }

    public function test_empty_recent_payments_state_renders(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses(paymentFixture: 'payments-empty.json');

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ยังไม่มีรายการชำระเงินล่าสุด');
    }

    public function test_dashboard_makes_one_payment_call_per_lifecycle(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ภาพรวมการชำระเงิน');

        $paymentCalls = 0;
        foreach (Http::recorded() as [$request]) {
            if (str_contains($request->url(), '/payments')) {
                $paymentCalls++;
            }
        }

        $this->assertSame(1, $paymentCalls);
    }

    public function test_payment_widget_loading_skeleton_and_cache_metadata_render(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('กำลังโหลดภาพรวมการชำระเงิน')
            ->assertSee('aria-busy="true"', false);
    }

    public function test_repeated_dashboard_load_within_ttl_uses_cached_payment_overview(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)->assertSee('ภาพรวมการชำระเงิน');
        Livewire::test(CustomerDashboard::class)->assertSee('ข้อมูลจาก cache');

        $this->assertSame(1, $this->paymentCallCount());
    }

    public function test_dashboard_refresh_bypasses_payment_cache_once(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeDashboardResponses();

        Livewire::test(CustomerDashboard::class)
            ->assertSee('ภาพรวมการชำระเงิน')
            ->call('refresh')
            ->assertSet('pageError', null);

        $this->assertSame(2, $this->paymentCallCount());
    }

    private function paymentCallCount(): int
    {
        $paymentCalls = 0;
        foreach (Http::recorded() as [$request]) {
            if (str_contains($request->url(), '/payments')) {
                $paymentCalls++;
            }
        }

        return $paymentCalls;
    }

    private function fakeDashboardResponses(string $paymentFixture = 'payments-index-success.json', int $paymentStatus = 200): void
    {
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/payments*' => Http::response($this->fixture($paymentFixture), $paymentStatus),
            'https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

                if (($query['order_status'] ?? null) === 'completed') {
                    return Http::response($this->shipmentResponse([], 11, 1));
                }

                if (($query['order_status'] ?? null) === 'problem') {
                    return Http::response($this->shipmentResponse([$this->shipment(90001, 'OH90001', 'problem')], 2, 5));
                }

                if (($query['per_page'] ?? null) === '1') {
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
    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'Selected Account']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::PaymentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001, 'can_view_payment' => true]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

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
            'items' => [],
        ];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
