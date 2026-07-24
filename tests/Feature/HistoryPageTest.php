<?php

namespace Tests\Feature;

use App\Application\History\ListOrderHistory;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Livewire\History\OrderHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_open_history(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        $this->get(route('history'))
            ->assertOk()
            ->assertSee('ประวัติรายการ')
            ->assertSee('OH10001')
            ->assertDontSee('secret-api-key');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('history'))->assertRedirect(route('login'));
    }

    public function test_account_without_capability_shows_safe_authorization_state(): void
    {
        [$user, $account] = $this->eligibleAccount(withCapability: false);
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->assertSet('unavailable', true)
            ->assertSee('Client Account นี้ยังไม่มีสิทธิ์ดูประวัติรายการ');
    }

    public function test_default_30_day_filters_are_shown(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->assertSet('datePreset', ListOrderHistory::PRESET_LAST_30_DAYS)
            ->assertSet('dateFrom', '2026-06-18')
            ->assertSet('dateTo', '2026-07-17');
    }

    public function test_date_presets_update_dates(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->call('selectDatePreset', ListOrderHistory::PRESET_TODAY)
            ->assertSet('dateFrom', '2026-07-17')
            ->assertSet('dateTo', '2026-07-17')
            ->call('selectDatePreset', ListOrderHistory::PRESET_LAST_7_DAYS)
            ->assertSet('dateFrom', '2026-07-11')
            ->call('selectDatePreset', ListOrderHistory::PRESET_LAST_30_DAYS)
            ->assertSet('dateFrom', '2026-06-18')
            ->call('selectDatePreset', ListOrderHistory::PRESET_CURRENT_MONTH)
            ->assertSet('dateFrom', '2026-07-01');
    }

    public function test_custom_date_range_works_and_invalid_range_shows_thai_error(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->set('dateFrom', '2026-07-01')
            ->set('dateTo', '2026-07-15')
            ->call('search')
            ->assertSet('datePreset', ListOrderHistory::PRESET_CUSTOM)
            ->assertHasNoErrors()
            ->set('dateFrom', '2026-07-17')
            ->set('dateTo', '2026-07-01')
            ->call('search')
            ->assertHasErrors(['dateTo'])
            ->assertSee('วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น');
    }

    public function test_results_and_localized_status_render(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->assertSee('OH10001')
            ->assertSee('นำส่งแล้ว')
            ->assertSee(route('shipments.show', '10001'));
    }

    public function test_empty_state_renders(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 1]])]);

        Livewire::test(OrderHistory::class)
            ->assertSee('ยังไม่มีประวัติรายการ')
            ->assertSee('สร้างรายการแรก');
    }

    public function test_filter_changes_reset_pagination_and_pagination_preserves_filters(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList(lastPage: 3);

        Livewire::test(OrderHistory::class)
            ->call('nextPage')
            ->assertSet('page', 2)
            ->set('keyword', '10001')
            ->call('search')
            ->assertSet('page', 1)
            ->call('nextPage')
            ->assertSet('page', 2);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'tracking_no=10001') && str_contains($request->url(), 'page=2'));
    }

    public function test_clear_filters_restores_defaults_and_refresh_retains_filters(): void
    {
        CarbonImmutable::setTestNow('2026-07-17 10:00:00');
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->set('keyword', 'OH10001')
            ->set('status', 'delivered')
            ->call('refresh')
            ->assertSet('keyword', 'OH10001')
            ->assertSet('status', 'delivered')
            ->call('clearFilters')
            ->assertSet('datePreset', ListOrderHistory::PRESET_LAST_30_DAYS)
            ->assertSet('dateFrom', '2026-06-18')
            ->assertSet('keyword', '')
            ->assertSet('status', '');
    }

    public function test_safe_connection_error_is_shown(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(fn () => throw new ConnectionException('timed out with secret-api-key'));

        Livewire::test(OrderHistory::class)
            ->assertSet('pageError', 'ไม่สามารถเชื่อมต่อ Sisahygo ได้ กรุณาลองใหม่อีกครั้ง')
            ->assertDontSee('secret-api-key');
    }

    public function test_malformed_response_shows_safe_error(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response(['meta' => []])]);

        Livewire::test(OrderHistory::class)
            ->assertSet('pageError', 'รูปแบบข้อมูลจาก Sisahygo ไม่ตรงตามที่คาดไว้');
    }

    public function test_selected_account_stays_stable_during_hydrated_actions_without_container_binding(): void
    {
        $user = User::factory()->create();
        $other = $this->accountFor($user, 'Other', 'other-secret');
        $selected = $this->accountFor($user, 'Selected', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selected->id]);
        $this->fakeHistoryList();
        app()->forgetInstance(ClientAccount::class);

        Livewire::test(OrderHistory::class)
            ->call('selectDatePreset', ListOrderHistory::PRESET_TODAY)
            ->call('nextPage')
            ->call('refresh')
            ->assertSet('pageError', null);

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
        $this->assertTrue($other->exists);
    }

    public function test_recent_receivers_and_products_render_from_visible_records(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeHistoryList();

        Livewire::test(OrderHistory::class)
            ->assertSee('ผู้รับที่ใช้ล่าสุด')
            ->assertSee('บริษัท รับสินค้าไทย จำกัด')
            ->assertSee('สินค้าที่ใช้ล่าสุด')
            ->assertSee('น้ำดื่ม 600 ml');

        Http::assertSentCount(1);
    }

    private function fakeHistoryList(int $lastPage = 1): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => Http::response($this->historyFixture($lastPage))]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(bool $withCapability = true): array
    {
        $user = User::factory()->create();
        $account = $this->accountFor($user, 'Selected Account', 'secret-api-key', $withCapability);

        return [$user, $account];
    }

    private function accountFor(User $user, string $name, string $apiKey, bool $withCapability = true): ClientAccount
    {
        $account = ClientAccount::factory()->create(['name' => $name]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        if ($withCapability) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        }
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return $account;
    }

    /** @return array<string, mixed> */
    private function historyFixture(int $lastPage = 1): array
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
            ],
            'meta' => ['current_page' => 1, 'per_page' => 15, 'total' => 30, 'last_page' => $lastPage],
        ];
    }
}
