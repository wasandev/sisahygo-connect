<?php

namespace Tests\Feature;

use App\Application\Payment\PaymentQueryService;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_sends_expected_filters_and_omits_null_values(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakePaymentList();

        $result = app(PaymentQueryService::class)->list($user, $account, [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'payment_status' => 'paid',
            'payment_type' => 'F',
            'order_header_no' => 'OH-F-1001',
            'client_reference_no' => 'REF-F',
            'sender_customer_ids' => [10001],
            'page' => 2,
            'per_page' => 20,
        ]);

        $this->assertSame('AR-P-1001', $result['items'][0]['payment_identifier']);
        $this->assertSame('2,350.50', $result['summary']['total_amount_display']);
        $this->assertSame(3, $result['meta']['total']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'from_date=2026-07-01')
            && str_contains($request->url(), 'to_date=2026-07-31')
            && str_contains($request->url(), 'payment_status=paid')
            && str_contains($request->url(), 'payment_type=F')
            && str_contains($request->url(), 'order_header_no=OH-F-1001')
            && str_contains($request->url(), 'client_reference_no=REF-F')
            && str_contains($request->url(), 'page=2')
            && str_contains($request->url(), 'per_page=20')
            && ! str_contains($request->url(), 'sender_customer_ids'));
    }

    public function test_maps_f_l_and_e_without_selecting_h_or_t(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->fakePaymentList();

        $items = app(PaymentQueryService::class)->list($user, $account)['items'];

        $this->assertSame(['F', 'L', 'E'], array_column($items, 'payment_type'));
        $this->assertSame('วางบิลต้นทาง', $items[0]['payment_type_label']);
        $this->assertSame('ผู้ส่ง', $items[0]['payer_role_label']);
        $this->assertSame('วางบิลปลายทาง', $items[1]['payment_type_label']);
        $this->assertSame('เก็บเงินปลายทาง', $items[2]['payment_type_label']);
    }

    public function test_nullable_amounts_are_not_fabricated(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments/BR-2001' => Http::response($this->fixture('payment-detail-e-success.json'))]);

        $payment = app(PaymentQueryService::class)->detail($user, $account, 'BR-2001');

        $this->assertNull($payment['paid_amount']);
        $this->assertSame('—', $payment['paid_amount_display']);
        $this->assertSame('350.00', $payment['total_amount_display']);
    }

    public function test_money_formatter_preserves_large_decimal_strings_and_invalid_optional_dates_are_neutral(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments/AR-P-9999' => Http::response([
            'data' => [
                'payment_identifier' => 'AR-P-9999',
                'payment_type' => 'F',
                'payer_role' => 'sender',
                'billing_date' => 'not-a-date',
                'payment_status' => 'outstanding',
                'total_amount' => '12345678901234567890.10',
                'paid_amount' => 'not-money',
                'sender' => ['customer_id' => 10001, 'name' => 'Sender Co'],
                'receiver' => ['customer_id' => 20001, 'name' => 'Receiver Co'],
            ],
        ])]);

        $payment = app(PaymentQueryService::class)->detail($user, $account, 'AR-P-9999');

        $this->assertSame('12,345,678,901,234,567,890.10', $payment['total_amount_display']);
        $this->assertSame('—', $payment['paid_amount_display']);
        $this->assertSame('—', $payment['billing_date']);
        $this->assertArrayNotHasKey('customer_id', $payment['sender']);
        $this->assertArrayNotHasKey('customer_id', $payment['receiver']);
    }

    public function test_rejects_h_and_t_filters_before_core_call(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake();

        $this->expectException(ValidationException::class);

        try {
            app(PaymentQueryService::class)->list($user, $account, ['payment_type' => 'H']);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_detail_uses_public_identifier(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments/AR-P-1001' => Http::response($this->fixture('payment-detail-f-success.json'))]);

        $payment = app(PaymentQueryService::class)->detail($user, $account, 'AR-P-1001');

        $this->assertSame('INV-F-1', $payment['invoice']['number']);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/payments/AR-P-1001');
    }

    public function test_core_not_found_maps_safely(): void
    {
        [$user, $account] = $this->eligibleAccount();
        Http::fake(['*' => Http::response($this->fixture('not-found.json'), 404)]);

        try {
            app(PaymentQueryService::class)->list($user, $account);
            $this->fail('Expected not found exception.');
        } catch (\Throwable $caught) {
            $this->assertInstanceOf(SisahygoNotFoundException::class, $caught);
            $this->assertStringNotContainsString('secret-api-key', $caught->getMessage());
        }
    }

    public function test_suspended_account_is_rejected(): void
    {
        [$user, $account] = $this->eligibleAccount(ClientAccountStatus::Suspended);

        $this->expectException(AuthorizationException::class);

        app(PaymentQueryService::class)->list($user, $account);
    }

    private function fakePaymentList(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/payments*' => Http::response($this->fixture('payments-index-success.json'))]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(ClientAccountStatus $status = ClientAccountStatus::Active): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['status' => $status]);
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
