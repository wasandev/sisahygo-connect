<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentClientAccountContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_no_client_account_receives_safe_result(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee('ยังไม่มีบัญชีลูกค้าที่พร้อมใช้งาน');
    }

    public function test_user_with_one_account_is_selected_automatically(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccountFor($user, 'ABC');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas(CurrentClientAccountResolver::SESSION_KEY, $account->id)
            ->assertSee('ABC Company');
    }

    public function test_user_with_multiple_accounts_is_redirected_to_selection(): void
    {
        $user = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $this->createAccountFor($user, 'DEF');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('client-accounts.select'));
    }

    public function test_account_selection_lists_only_active_accounts_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $this->createAccountFor($user, 'DEF');
        $this->createAccountFor($otherUser, 'XYZ');

        $this->actingAs($user)
            ->get(route('client-accounts.select'))
            ->assertOk()
            ->assertSee('ABC Company')
            ->assertSee('DEF Company')
            ->assertDontSee('XYZ Company');
    }

    public function test_user_can_select_only_an_account_they_belong_to(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $otherAccount = $this->createAccountFor($otherUser, 'XYZ');

        $this->actingAs($user)
            ->post(route('client-accounts.select.store'), [
                'client_account_id' => $otherAccount->id,
            ])
            ->assertForbidden();

        $this->assertNotEquals($otherAccount->id, session(CurrentClientAccountResolver::SESSION_KEY));
    }

    public function test_tampered_session_account_id_is_rejected(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $otherAccount = $this->createAccountFor($otherUser, 'XYZ');

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $otherAccount->id])
            ->get(route('dashboard'))
            ->assertRedirect(route('client-accounts.select'))
            ->assertSessionMissing(CurrentClientAccountResolver::SESSION_KEY);
    }

    public function test_inactive_membership_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccountFor($user, 'ABC', membershipActive: false);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_client_account_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccountFor($user, 'ABC', status: ClientAccountStatus::Suspended);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_account_switching_updates_active_context_safely(): void
    {
        $user = User::factory()->create();
        $firstAccount = $this->createAccountFor($user, 'ABC');
        $secondAccount = $this->createAccountFor($user, 'DEF');

        $this->actingAs($user)
            ->post(route('client-accounts.select.store'), [
                'client_account_id' => $firstAccount->id,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(CurrentClientAccountResolver::SESSION_KEY, $firstAccount->id);

        $this->actingAs($user)
            ->post(route('client-accounts.change'))
            ->assertRedirect(route('client-accounts.select'))
            ->assertSessionMissing(CurrentClientAccountResolver::SESSION_KEY);

        $this->actingAs($user)
            ->post(route('client-accounts.select.store'), [
                'client_account_id' => $secondAccount->id,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas(CurrentClientAccountResolver::SESSION_KEY, $secondAccount->id);
    }

    public function test_logout_remains_available_without_selected_client_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    private function createAccountFor(
        User $user,
        string $code,
        bool $membershipActive = true,
        ClientAccountStatus $status = ClientAccountStatus::Active,
    ): ClientAccount {
        $account = ClientAccount::create([
            'name' => $code.' Company',
            'code' => $code,
            'status' => $status,
        ]);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => $membershipActive,
            'joined_at' => now(),
        ]);

        return $account;
    }
}