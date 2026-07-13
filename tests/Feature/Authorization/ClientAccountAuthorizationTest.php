<?php

namespace Tests\Feature\Authorization;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ClientAccountAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_with_users_manage_capability_can_manage_users(): void
    {
        [$user, $account] = $this->createMembership(ClientAccountRole::Owner);
        $this->enableCapability($account, ClientCapability::UsersManage);

        $this->assertTrue(Gate::forUser($user)->allows('manageUsers', $account));
    }

    public function test_viewer_with_users_manage_capability_cannot_manage_users(): void
    {
        [$user, $account] = $this->createMembership(ClientAccountRole::Viewer);
        $this->enableCapability($account, ClientCapability::UsersManage);

        $this->assertFalse(Gate::forUser($user)->allows('manageUsers', $account));
    }

    public function test_user_from_another_client_account_cannot_view_account(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);

        $this->assertFalse(Gate::forUser($user)->allows('view', $account));
    }

    public function test_shipment_policy_requires_membership_and_capability(): void
    {
        [$user, $account] = $this->createMembership(ClientAccountRole::Operator);

        $this->assertFalse(Gate::forUser($user)->allows('shipment.viewAny', $account));

        $this->enableCapability($account, ClientCapability::ShipmentView);

        $this->assertTrue(Gate::forUser($user)->allows('shipment.viewAny', $account));
    }

    private function createMembership(ClientAccountRole $role): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::create(['name' => fake()->company(), 'code' => fake()->unique()->bothify('ACCT-###')]);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [$user, $account];
    }

    private function enableCapability(ClientAccount $account, ClientCapability $capability): void
    {
        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => $capability,
            'is_enabled' => true,
        ]);
    }
}