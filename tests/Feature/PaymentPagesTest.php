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
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_selected_account_can_open_payment_center(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        $this->get(route('payments'))
            ->assertOk()
            ->assertSee('ศูนย์การชำระเงิน')
            ->assertSee('AR-P-1001')
            ->assertSee('REF-F')
            ->assertSee('Selected Account')
            ->assertSee('รายการชำระแล้ว')
            ->assertDontSee('secret-api-key');
    }

    public function test_account_selection_middleware_remains_enforced(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('payments'))
            ->assertForbidden();
    }

    public function test_filters_are_sent_and_page_resets(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->set('page', 3)
            ->set('dateFrom', '2026-07-01')
            ->set('dateTo', '2026-07-31')
            ->set('paymentType', 'E')
            ->set('paymentStatus', 'outstanding')
            ->set('orderHeaderNo', 'OH-E-2001')
            ->set('clientReferenceNo', 'REF-E')
            ->call('search')
            ->assertSet('page', 1)
            ->assertSee('เก็บเงินปลายทาง');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'payment_type=E')
            && str_contains($request->url(), 'payment_status=outstanding')
            && str_contains($request->url(), 'order_header_no=OH-E-2001')
            && str_contains($request->url(), 'client_reference_no=REF-E')
            && str_contains($request->url(), 'from_date=2026-07-01')
            && str_contains($request->url(), 'to_date=2026-07-31'));
    }

    public function test_query_string_hydrates_active_filter_chips(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        $this->get(route('payments', ['payment_status' => 'outstanding', 'payment_type' => 'F']))
            ->assertOk()
            ->assertSee('ประเภท: วางบิลต้นทาง')
            ->assertSee('สถานะ: ค้างชำระ');
    }

    public function test_clear_one_filter_preserves_other_filters(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->set('paymentType', 'F')
            ->set('paymentStatus', 'outstanding')
            ->set('page', 3)
            ->call('clearFilter', 'paymentType')
            ->assertSet('paymentType', '')
            ->assertSet('paymentStatus', 'outstanding')
            ->assertSet('page', 1)
            ->assertSee('สถานะ: ค้างชำระ');
    }

    public function test_type_status_date_and_per_page_changes_reset_page(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->set('page', 3)
            ->set('paymentType', 'F')
            ->assertSet('page', 1)
            ->set('page', 3)
            ->set('paymentStatus', 'paid')
            ->assertSet('page', 1)
            ->set('page', 3)
            ->set('dateFrom', '2026-07-01')
            ->assertSet('page', 1)
            ->set('page', 3)
            ->set('perPage', 50)
            ->assertSet('page', 1);
    }

    public function test_refresh_preserves_filters_and_does_not_expose_credentials(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->set('paymentType', 'E')
            ->set('paymentStatus', 'outstanding')
            ->set('page', 2)
            ->call('refresh')
            ->assertSet('paymentType', 'E')
            ->assertSet('paymentStatus', 'outstanding')
            ->assertSet('page', 2)
            ->assertDontSee('secret-api-key');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'payment_type=E')
            && str_contains($request->url(), 'payment_status=outstanding')
            && str_contains($request->url(), 'page=2'));
    }

    public function test_h_and_t_filters_are_not_available(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->assertSee('วางบิลต้นทาง')
            ->assertDontSee('เงินสดต้นทาง')
            ->assertDontSee('เงินโอนต้นทาง');
    }

    public function test_empty_state_renders(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments*' => Http::response($this->fixture('payments-empty.json'))]);

        Livewire::test(PaymentIndex::class)
            ->assertSee('ยังไม่มีรายการชำระเงิน');
    }

    public function test_api_failure_does_not_render_as_empty(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response($this->fixture('server-error.json'), 500)]);

        Livewire::test(PaymentIndex::class)
            ->assertSee('Sisahygo Core ไม่พร้อมให้บริการชั่วคราว')
            ->assertDontSee('ยังไม่มีรายการชำระเงิน');
    }

    public function test_validation_errors_render_in_thai(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->set('dateFrom', '2026-07-31')
            ->set('dateTo', '2026-07-01')
            ->call('search')
            ->assertHasErrors(['dateTo'])
            ->assertSee('วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น');
    }

    public function test_detail_omits_empty_invoice_and_receipt_sections(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments/BR-2001' => Http::response($this->fixture('payment-detail-e-success.json'))]);

        Livewire::test(PaymentShow::class, ['paymentIdentifier' => 'BR-2001'])
            ->assertSee('BR-2001')
            ->assertDontSee('เลขที่ Invoice')
            ->assertDontSee('เลขที่ Receipt');
    }

    public function test_rendered_payment_center_does_not_expose_internal_customer_ids(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakePaymentList();

        Livewire::test(PaymentIndex::class)
            ->assertDontSee('10001')
            ->assertDontSee('20001');
    }

    public function test_detail_renders_f_l_and_e(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);

        foreach ([
            ['AR-P-1001', 'payment-detail-f-success.json', 'วางบิลต้นทาง'],
            ['AR-P-1002', 'payment-detail-l-success.json', 'วางบิลปลายทาง'],
            ['BR-2001', 'payment-detail-e-success.json', 'เก็บเงินปลายทาง'],
        ] as [$identifier, $fixture, $label]) {
            Http::fake(["https://sandbox-api.sisahygo.online/api/v1/client/payments/{$identifier}" => Http::response($this->fixture($fixture))]);

            Livewire::test(PaymentShow::class, ['paymentIdentifier' => $identifier])
                ->assertSee($identifier)
                ->assertSee($label)
                ->assertDontSee('secret-api-key');
        }
    }

    public function test_inaccessible_detail_renders_safe_not_found(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response($this->fixture('not-found.json'), 404)]);

        Livewire::test(PaymentShow::class, ['paymentIdentifier' => 'AR-P-9999'])
            ->assertSee('ไม่พบรายการชำระเงิน');
    }

    public function test_navigation_active_state_covers_detail_page(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments/AR-P-1001' => Http::response($this->fixture('payment-detail-f-success.json'))]);

        $this->get(route('payments.show', 'AR-P-1001'))
            ->assertOk()
            ->assertSee('การชำระเงิน');
    }

    private function fakePaymentList(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments*' => Http::response($this->fixture('payments-index-success.json'))]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'Selected Account']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::PaymentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001, 'can_view_payment' => true]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
