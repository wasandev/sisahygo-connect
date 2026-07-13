<?php

namespace App\Domain\ClientAccount\Services;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;

class CurrentClientAccountResolver
{
    public function resolveForUser(User $user): ?ClientAccount
    {
        return ClientAccount::query()
            ->whereHas('memberships', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('is_active', true)
            )
            ->where('status', 'active')
            ->orderBy('name')
            ->first();
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