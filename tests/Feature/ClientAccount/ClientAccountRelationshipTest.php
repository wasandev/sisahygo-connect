<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccountRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_account_can_have_users_customers_and_capabilities(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 1001,
            'can_send' => true,
            'can_receive' => true,
            'can_view_payment' => true,
            'is_default_sender' => true,
            'is_default_receiver' => false,
            'is_active' => true,
        ]);

        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => ClientCapability::UsersManage,
            'is_enabled' => true,
        ]);

        $this->assertTrue($user->clientAccounts()->whereKey($account->id)->exists());
        $this->assertSame(1, $account->memberships()->count());
        $this->assertSame(1, $account->customerLinks()->count());
        $this->assertSame(1, $account->capabilities()->count());
    }
}