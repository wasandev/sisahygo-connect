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
use App\Livewire\OrderChecking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCheckingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_checking_page_renders_for_eligible_account(): void
    {
        [$user] = $this->eligibleAccount();
        $this->fakeUnitsOnly();

        $this->actingAs($user)
            ->get(route('order-checking'))
            ->assertOk()
            ->assertSee('สร้างรายการส่งสินค้า')
            ->assertSee('1. ผู้รับสินค้า')
            ->assertSee('2. รายการสินค้า')
            ->assertSee('3. หมายเหตุและเลขอ้างอิง')
            ->assertSee('4. ตรวจทานและยืนยัน');
    }

    public function test_receiver_only_account_shows_unavailable_state(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        ClientAccountCustomer::factory()->for($account)->receiver()->create(['customer_id' => 20001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $this->actingAs($user)
            ->get(route('order-checking'))
            ->assertOk()
            ->assertSee('ยังไม่พร้อมสร้างรายการส่งสินค้า')
            ->assertSee('ยังไม่มีสิทธิ์ส่งสินค้า');
    }

    public function test_receiver_search_uses_selected_active_account_from_session(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReferenceData();

        Livewire::test(OrderChecking::class)
            ->set('receiverSearch', 'รับ')
            ->assertSet('pageError', null)
            ->assertSet('receiverResults.0.customer_id', 20001);
    }

    public function test_product_search_uses_selected_active_account_from_session(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeReferenceData();

        Livewire::test(OrderChecking::class)
            ->set('productSearch', 'น้ำ')
            ->assertSet('pageError', null)
            ->assertSet('productResults.0.product_id', 6639);
    }

    public function test_receiver_and_product_search_add_item_with_stable_row_keys(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        $this->fakeReferenceData();

        $component = Livewire::test(OrderChecking::class)
            ->set('receiverSearch', 'รับ')
            ->assertSee('บริษัท รับสินค้าไทย จำกัด')
            ->call('selectReceiver', 20001)
            ->assertSet('selectedReceiver.customer_id', 20001)
            ->set('productSearch', 'น้ำ')
            ->assertSee('น้ำดื่ม 600 ml');

        $initialRowKey = $component->get('items')[0]['row_key'];

        $component->call('addProduct', 6639, 1)
            ->assertSet('items.0.product_id', 6639)
            ->assertSet('items.0.row_key', $initialRowKey);

        $this->assertCount(1, $component->get('items'));

        $component->call('addProduct', 7001, 3)
            ->assertSet('items.1.product_id', 7001);

        $secondRowKey = $component->get('items')[1]['row_key'];

        $component->call('removeItem', $initialRowKey)
            ->assertSet('items.0.product_id', 7001)
            ->assertSet('items.0.row_key', $secondRowKey);
    }

    public function test_valid_submission_posts_once_and_shows_checking_success(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        $this->fakeReferenceDataWithSubmit();

        Livewire::test(OrderChecking::class)
            ->set('selectedReceiver', ['customer_id' => 20001, 'name' => 'บริษัท รับสินค้าไทย จำกัด', 'phone' => '020000001'])
            ->set('items.0.product_id', 6639)
            ->set('items.0.product_name', 'น้ำดื่ม 600 ml')
            ->set('items.0.unit_id', 1)
            ->set('items.0.unit_name', 'ขวด')
            ->set('clientReferenceNo', 'SC-20260716-ABC123')
            ->call('submit')
            ->assertSet('state', 'success')
            ->assertSee('รอตรวจสอบ');

        Http::assertSentCount(5);
        Http::assertSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_api_422_maps_to_stable_item_error(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response($this->fixture('products-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings' => Http::response($this->fixture('order-checking-validation-error.json'), 422),
        ]);

        $component = Livewire::test(OrderChecking::class)
            ->set('selectedReceiver', ['customer_id' => 20001, 'name' => 'บริษัท รับสินค้าไทย จำกัด', 'phone' => '020000001'])
            ->set('items.0.product_id', 6639)
            ->set('items.0.product_name', 'น้ำดื่ม 600 ml')
            ->set('items.0.unit_id', 1)
            ->set('items.0.unit_name', 'ขวด')
            ->set('clientReferenceNo', 'SC-20260716-ABC123');

        $rowKey = $component->get('items')[0]['row_key'];

        $component->call('submit')
            ->assertSet('state', 'api_validation_failed')
            ->assertHasErrors(["items.{$rowKey}.product_id"]);
    }

    public function test_timeout_enters_unknown_state_and_reconciliation_can_find_order(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(function ($request) {
            if ($request->method() === 'POST') {
                throw new ConnectionException('timed out');
            }

            if (str_contains($request->url(), '/order-checkings/SC-20260716-ABC123')) {
                return Http::response($this->fixture('order-checking-lookup-success.json'));
            }

            if (str_contains($request->url(), '/receivers')) {
                return Http::response($this->fixture('receivers-success.json'));
            }

            if (str_contains($request->url(), '/products')) {
                return Http::response($this->fixture('products-success.json'));
            }

            return Http::response($this->fixture('units-success.json'));
        });

        Livewire::test(OrderChecking::class)
            ->set('selectedReceiver', ['customer_id' => 20001, 'name' => 'บริษัท รับสินค้าไทย จำกัด', 'phone' => '020000001'])
            ->set('items.0.product_id', 6639)
            ->set('items.0.product_name', 'น้ำดื่ม 600 ml')
            ->set('items.0.unit_id', 1)
            ->set('items.0.unit_name', 'ขวด')
            ->set('clientReferenceNo', 'SC-20260716-ABC123')
            ->call('submit')
            ->assertSet('state', 'unknown_result')
            ->call('reconcile')
            ->assertSet('state', 'success');
    }

    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function fakeUnitsOnly(): void
    {
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);
    }

    private function fakeReferenceData(): void
    {
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response($this->fixture('products-success.json')),
        ]);
    }

    private function fakeReferenceDataWithSubmit(): void
    {
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/receivers*' => Http::response($this->fixture('receivers-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/products*' => Http::response($this->fixture('products-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings' => Http::response($this->fixture('order-checking-success.json'), 201),
        ]);
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
