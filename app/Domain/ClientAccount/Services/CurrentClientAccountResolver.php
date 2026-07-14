<?php

namespace App\Domain\ClientAccount\Services;

use App\Domain\ClientAccount\Enums\CurrentClientAccountResolutionStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Support\CurrentClientAccountResolution;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CurrentClientAccountResolver
{
    public const SESSION_KEY = 'selected_client_account_id';

    /**
     * @return Collection<int, ClientAccount>
     */
    public function activeAccountsForUser(User $user): Collection
    {
        return ClientAccount::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('is_active', true)
            )
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function resolve(User $user, mixed $selectedClientAccountId = null): CurrentClientAccountResolution
    {
        $activeAccounts = $this->activeAccountsForUser($user);

        if ($activeAccounts->isEmpty()) {
            return new CurrentClientAccountResolution(CurrentClientAccountResolutionStatus::NoAccounts);
        }

        if ($selectedClientAccountId !== null) {
            $selectedAccount = $this->findAuthorizedForUser($user, (int) $selectedClientAccountId);

            return $selectedAccount
                ? new CurrentClientAccountResolution(CurrentClientAccountResolutionStatus::Selected, $selectedAccount)
                : new CurrentClientAccountResolution(CurrentClientAccountResolutionStatus::InvalidSelection);
        }

        if ($activeAccounts->count() === 1) {
            return new CurrentClientAccountResolution(
                CurrentClientAccountResolutionStatus::Selected,
                $activeAccounts->first(),
            );
        }

        return new CurrentClientAccountResolution(CurrentClientAccountResolutionStatus::SelectionRequired);
    }

    public function resolveForUser(User $user): ?ClientAccount
    {
        return $this->resolve($user, request()->session()->get(self::SESSION_KEY))->clientAccount;
    }

    public function findAuthorizedForUser(User $user, int $clientAccountId): ?ClientAccount
    {
        return ClientAccount::query()
            ->whereKey($clientAccountId)
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('is_active', true)
            )
            ->where('status', 'active')
            ->first();
    }
}
