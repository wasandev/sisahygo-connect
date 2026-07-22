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
use App\Livewire\OrderCheckingBulk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BulkOrderCheckingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_route_requires_authentication(): void
    {
        $this->get(route('order-checking.bulk'))->assertRedirect(route('login'));
    }

    public function test_bulk_route_requires_selected_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('order-checking.bulk'))
            ->assertForbidden();
    }

    public function test_bulk_form_renders_active_order_editor_and_review_action(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $this->get(route('order-checking.bulk'))
            ->assertOk()
            ->assertSee('สร้างรายการตรวจสอบแบบหลายรายการ')
            ->assertSee('ข้อมูล Batch')
            ->assertSee('รายการที่ 1')
            ->assertSee('ตรวจสอบรายการ')
            ->assertDontSee('Core internal');
    }

    public function test_add_remove_order_and_item_limits_are_enforced(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $component = Livewire::test(OrderCheckingBulk::class)
            ->call('addOrder');

        $this->assertCount(2, $component->get('orders'));
        $this->assertSame($component->get('orders')[1]['row_key'], $component->get('activeOrderKey'));

        $firstOrderKey = $component->get('orders')[0]['row_key'];
        $firstItemKey = $component->get('orders')[0]['items'][0]['row_key'];

        $component->call('addItem', $firstOrderKey);
        $this->assertCount(2, $component->get('orders')[0]['items']);

        $component->call('removeItem', $firstOrderKey, $firstItemKey);
        $this->assertCount(1, $component->get('orders')[0]['items']);

        for ($i = 0; $i < 60; $i++) {
            $component->call('addOrder');
        }

        $this->assertCount(50, $component->get('orders'));
    }

    public function test_duplicate_active_order_copies_context_but_requires_new_references(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $order = $this->uiOrder('BC-20260721-001');
        $component = Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$order])
            ->set('activeOrderKey', $order['row_key'])
            ->set('activeItemKey', $order['items'][0]['row_key'])
            ->call('duplicateActiveOrder')
            ->assertSee('คัดลอกรายการแล้ว');

        $orders = $component->get('orders');
        $this->assertCount(2, $orders);
        $this->assertSame('BC-20260721-001', $orders[0]['client_reference_no']);
        $this->assertNotSame('', $orders[1]['client_reference_no']);
        $this->assertNotSame($orders[0]['client_reference_no'], $orders[1]['client_reference_no']);
        $this->assertSame(20001, $orders[1]['customer_rec_id']);
        $this->assertSame('น้ำดื่ม 600 ml', $orders[1]['items'][0]['product_name']);
        $this->assertSame('', $orders[1]['items'][0]['client_line_id']);
        $this->assertSame('', $orders[1]['items'][0]['client_item_no']);
        $this->assertSame($orders[1]['row_key'], $component->get('activeOrderKey'));

        $component->set('orders.1.client_reference_no', 'BC-20260721-NEW');
        $orders = $component->get('orders');
        $this->assertSame('BC-20260721-001', $orders[0]['client_reference_no']);
        $this->assertSame('BC-20260721-NEW', $orders[1]['client_reference_no']);
    }

    public function test_duplicate_client_reference_is_rejected_before_core_submit(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk' => Http::response($this->fixture('order-checking-bulk-success.json'), 201),
        ]);

        $first = $this->uiOrder('BC-20260721-DUP');
        $second = $this->uiOrder('BC-20260721-DUP');

        Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$first, $second])
            ->call('beginReview')
            ->assertSet('step', 'edit')
            ->assertSet('state', 'request_rejected')
            ->assertSee('เลขอ้างอิงลูกค้าต้องไม่ซ้ำกันใน Batch');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/order-checkings/bulk'));
    }

    public function test_review_step_is_required_before_submit_and_does_not_call_core(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        Livewire::test(OrderCheckingBulk::class)
            ->set('batchReferenceNo', 'BATCH-20260721')
            ->set('orders', [$this->uiOrder('BC-20260721-001')])
            ->call('submit')
            ->assertSet('step', 'review')
            ->assertSet('state', 'review')
            ->assertSee('ตรวจสอบ Batch ก่อนส่ง');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/order-checkings/bulk'));
    }

    public function test_local_validation_returns_to_active_invalid_order(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $valid = $this->uiOrder('BC-20260721-001');
        $invalid = $this->uiOrder('');
        $invalid['customer_rec_id'] = null;
        $invalid['selected_receiver'] = null;

        Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$valid, $invalid])
            ->call('beginReview')
            ->assertSet('step', 'edit')
            ->assertSet('state', 'request_rejected')
            ->assertSet('activeOrderKey', $invalid['row_key'])
            ->assertSee('กรุณาตรวจสอบข้อมูลก่อนส่ง');
    }

    public function test_success_result_renders_batch_and_tracking_without_core_id(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk' => Http::response($this->fixture('order-checking-bulk-success.json'), 201),
        ]);

        Livewire::test(OrderCheckingBulk::class)
            ->set('batchReferenceNo', 'BATCH-20260721')
            ->set('orders', [$this->uiOrder('BC-20260721-001')])
            ->call('beginReview')
            ->call('confirmSubmit')
            ->assertSet('step', 'result')
            ->assertSet('state', 'all_succeeded')
            ->assertSet('dirty', false)
            ->assertSee('APIB202607210001')
            ->assertSee('12345')
            ->assertDontSee('Core internal');
    }

    public function test_result_filter_and_copy_controls_render_filtered_rows(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk' => Http::response($this->fixture('order-checking-bulk-partial.json'), 207),
        ]);

        Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$this->uiOrder('BC-20260721-001'), $this->uiOrder('BC-20260721-002')])
            ->call('beginReview')
            ->call('confirmSubmit')
            ->set('resultFilter', 'failed')
            ->assertSee('BC-20260721-002')
            ->assertDontSee('BC-20260721-001')
            ->assertSee('คัดลอกแถวที่แสดง');
    }

    public function test_partial_success_excludes_successful_rows_from_retry_and_requires_review_again(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk' => Http::response($this->fixture('order-checking-bulk-partial.json'), 207),
        ]);

        $component = Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$this->uiOrder('BC-20260721-001'), $this->uiOrder('BC-20260721-002')])
            ->call('beginReview')
            ->call('confirmSubmit')
            ->assertSet('state', 'partial_success')
            ->call('prepareFailedRetry')
            ->assertSet('step', 'edit')
            ->assertSet('state', 'editing')
            ->assertSet('dirty', true)
            ->call('submit')
            ->assertSet('step', 'review');

        $orders = $component->get('orders');
        $this->assertCount(1, $orders);
        $this->assertSame('BC-20260721-002', $orders[0]['client_reference_no']);
    }

    public function test_core_422_preserves_form_data_and_maps_nested_error(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/units*' => Http::response($this->fixture('units-success.json')),
            'https://sandbox-api.sisahygo.online/api/v1/client/order-checkings/bulk' => Http::response($this->fixture('order-checking-bulk-validation-error.json'), 422),
        ]);

        $component = Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$this->uiOrder('BC-20260721-001'), $this->uiOrder('BC-20260721-002')]);

        $secondOrderKey = $component->get('orders')[1]['row_key'];
        $firstItemKey = $component->get('orders')[1]['items'][0]['row_key'];

        $component->call('beginReview')
            ->call('confirmSubmit')
            ->assertSet('step', 'edit')
            ->assertSet('state', 'request_rejected')
            ->assertSet('orders.1.client_reference_no', 'BC-20260721-002')
            ->assertSet('activeOrderKey', $secondOrderKey)
            ->assertHasErrors(["orders.{$secondOrderKey}.items.{$firstItemKey}.amount"]);
    }

    public function test_transport_uncertainty_warns_against_blind_resubmission(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(fn ($request) => str_contains($request->url(), '/order-checkings/bulk')
            ? throw new ConnectionException('timed out')
            : Http::response($this->fixture('units-success.json')));

        Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$this->uiOrder('BC-20260721-001')])
            ->call('beginReview')
            ->call('confirmSubmit')
            ->assertSet('step', 'edit')
            ->assertSet('state', 'unknown_result')
            ->assertSee('ตรวจสอบเลขอ้างอิงแต่ละรายการก่อนส่งซ้ำ');
    }

    public function test_receiver_and_product_lookup_apply_only_to_active_context(): void
    {
        [$user, $account] = $this->eligibleAccount();
        app()->instance(ClientAccount::class, $account);
        $this->actingAs($user);
        Http::fake(['*' => Http::response($this->fixture('units-success.json'))]);

        $first = $this->uiOrder('BC-20260721-001');
        $second = $this->uiOrder('BC-20260721-002');
        $first['customer_rec_id'] = null;
        $first['selected_receiver'] = null;
        $second['customer_rec_id'] = null;
        $second['selected_receiver'] = null;
        $first['items'][0]['product_id'] = null;
        $first['items'][0]['product_name'] = '';
        $second['items'][0]['product_id'] = null;
        $second['items'][0]['product_name'] = '';

        $component = Livewire::test(OrderCheckingBulk::class)
            ->set('orders', [$first, $second])
            ->set('activeOrderKey', $second['row_key'])
            ->set('activeItemKey', $second['items'][0]['row_key'])
            ->set('receiverResults', [[
                'customer_id' => 20001,
                'name' => 'บริษัท รับสินค้าไทย จำกัด',
                'phone' => '020000001',
            ]])
            ->call('selectReceiver', null, 20001)
            ->set('productResults', [[
                'product_id' => 6639,
                'product_name' => 'น้ำดื่ม 600 ml',
                'unit_id' => 1,
                'unit_name' => 'ขวด',
            ]])
            ->call('selectProduct', null, null, 6639, 1);

        $orders = $component->get('orders');
        $this->assertNull($orders[0]['customer_rec_id']);
        $this->assertSame(20001, $orders[1]['customer_rec_id']);
        $this->assertNull($orders[0]['items'][0]['product_id']);
        $this->assertSame(6639, $orders[1]['items'][0]['product_id']);
    }

    private function uiOrder(string $reference): array
    {
        return [
            'row_key' => (string) str()->uuid(),
            'client_reference_no' => $reference,
            'customer_rec_id' => 20001,
            'selected_receiver' => ['customer_id' => 20001, 'name' => 'บริษัท รับสินค้าไทย จำกัด', 'phone' => '020000001'],
            'remark' => '',
            'items' => [[
                'row_key' => (string) str()->uuid(),
                'product_id' => 6639,
                'product_name' => 'น้ำดื่ม 600 ml',
                'unit_id' => 1,
                'unit_name' => 'ขวด',
                'amount' => '2.5000',
                'remark' => '',
                'client_line_id' => 'L1',
                'client_item_no' => 'ITEM001',
                'client_product_code' => 'FG001',
                'advanced_open' => false,
            ]],
        ];
    }

    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderBulk)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
