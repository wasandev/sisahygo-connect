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

class ClientAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_client_account_settings(): void
    {
        $this->get(route('settings.client-account'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_client_account_foundation_settings(): void
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
            'is_active' => true,
        ]);

        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => ClientCapability::SettingsManage,
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('Client Account')
            ->assertSee('ABC Company')
            ->assertSee('settings.manage');
    }

    public function test_authenticated_user_without_client_account_sees_safe_empty_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('No active client account is linked to this user yet');
    }
}