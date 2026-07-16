<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Livewire\OrderChecking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCheckingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_checking_page_renders_the_production_livewire_experience(): void
    {
        $user = User::factory()->create();
        $this->createClientAccountFor($user);

        $this->actingAs($user)
            ->get(route('order-checking'))
            ->assertOk()
            ->assertSee('ตรวจสอบรายการส่งสินค้า')
            ->assertSee('1. ผู้รับสินค้า')
            ->assertSee('2. รายการสินค้า')
            ->assertSee('3. หมายเหตุและเลขอ้างอิง')
            ->assertSee('4. ตรวจทานและยืนยัน')
            ->assertSee('ข้อมูลจำลองสำหรับ Sprint 2A-Prep');
    }

    public function test_mock_receiver_search_and_product_grid_are_stateful(): void
    {
        Livewire::test(OrderChecking::class)
            ->set('receiverSearch', 'คลัง')
            ->assertSee('หจก. คลังเหนือ')
            ->call('selectReceiver', 'receiver-north-warehouse')
            ->assertSet('selectedReceiverId', 'receiver-north-warehouse')
            ->call('addProduct', 'อะไหล่เครื่องจักร')
            ->assertSet('items.1.product', 'อะไหล่เครื่องจักร');
    }

    public function test_validation_states_block_incomplete_mock_confirmation(): void
    {
        Livewire::test(OrderChecking::class)
            ->set('selectedReceiverId', null)
            ->set('items', [])
            ->set('clientReferenceNo', '')
            ->call('confirmMockOrder')
            ->assertHasErrors(['selectedReceiverId', 'items', 'clientReferenceNo'])
            ->assertSet('showSuccessDialog', false);
    }

    public function test_complete_mock_order_shows_success_dialog_without_submission_integration(): void
    {
        Livewire::test(OrderChecking::class)
            ->call('confirmMockOrder')
            ->assertHasNoErrors()
            ->assertSet('showSuccessDialog', true)
            ->assertSee('จำลองการรับรายการสำเร็จ')
            ->assertSee('รอตรวจสอบ');
    }

    private function createClientAccountFor(User $user): ClientAccount
    {
        $account = ClientAccount::create([
            'name' => 'ABC Company',
            'code' => 'ABC',
        ]);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $account;
    }
}
