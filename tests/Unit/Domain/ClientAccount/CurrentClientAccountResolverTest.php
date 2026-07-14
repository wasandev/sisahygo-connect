<?php

namespace Tests\Unit\Domain\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\CurrentClientAccountResolutionStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentClientAccountResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_account_returns_no_accounts_status(): void
    {
        $user = User::factory()->create();

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user);

        $this->assertSame(CurrentClientAccountResolutionStatus::NoAccounts, $resolution->status);
        $this->assertNull($resolution->clientAccount);
    }

    public function test_one_account_is_selected_automatically(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccountFor($user, 'ABC');

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user);

        $this->assertSame(CurrentClientAccountResolutionStatus::Selected, $resolution->status);
        $this->assertTrue($account->is($resolution->clientAccount));
    }

    public function test_multiple_accounts_require_explicit_selection(): void
    {
        $user = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $this->createAccountFor($user, 'DEF');

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user);

        $this->assertSame(CurrentClientAccountResolutionStatus::SelectionRequired, $resolution->status);
        $this->assertNull($resolution->clientAccount);
    }

    public function test_valid_session_account_is_selected(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccountFor($user, 'ABC');
        $this->createAccountFor($user, 'DEF');

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user, $account->id);

        $this->assertSame(CurrentClientAccountResolutionStatus::Selected, $resolution->status);
        $this->assertTrue($account->is($resolution->clientAccount));
    }

    public function test_invalid_session_account_is_rejected(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createAccountFor($user, 'ABC');
        $otherAccount = $this->createAccountFor($otherUser, 'XYZ');

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user, $otherAccount->id);

        $this->assertSame(CurrentClientAccountResolutionStatus::InvalidSelection, $resolution->status);
        $this->assertNull($resolution->clientAccount);
    }

    public function test_inactive_membership_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);
        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => false,
        ]);

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user, $account->id);

        $this->assertSame(CurrentClientAccountResolutionStatus::NoAccounts, $resolution->status);
    }

    public function test_inactive_client_account_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::create([
            'name' => 'ABC Company',
            'code' => 'ABC',
            'status' => ClientAccountStatus::Suspended,
        ]);
        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => true,
        ]);

        $resolution = app(CurrentClientAccountResolver::class)->resolve($user, $account->id);

        $this->assertSame(CurrentClientAccountResolutionStatus::NoAccounts, $resolution->status);
    }

    private function createAccountFor(User $user, string $code): ClientAccount
    {
        $account = ClientAccount::create([
            'name' => $code.' Company',
            'code' => $code,
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
