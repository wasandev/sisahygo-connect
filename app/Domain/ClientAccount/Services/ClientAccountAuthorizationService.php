<?php

namespace App\Domain\ClientAccount\Services;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;

class ClientAccountAuthorizationService
{
    public function activeMembership(User $user, ClientAccount $clientAccount): ?ClientAccountUser
    {
        return ClientAccountUser::query()
            ->where('user_id', $user->id)
            ->where('client_account_id', $clientAccount->id)
            ->where('is_active', true)
            ->first();
    }

    public function isActiveMember(User $user, ClientAccount $clientAccount): bool
    {
        return $this->activeMembership($user, $clientAccount) !== null;
    }

    public function canManageAccount(User $user, ClientAccount $clientAccount): bool
    {
        $membership = $this->activeMembership($user, $clientAccount);

        return $membership?->role instanceof ClientAccountRole
            && $membership->role->canManageAccount();
    }

    public function hasCapability(ClientAccount $clientAccount, ClientCapability|string $capability): bool
    {
        $capability = $capability instanceof ClientCapability ? $capability->value : $capability;

        return $clientAccount->capabilities()
            ->where('capability', $capability)
            ->where('is_enabled', true)
            ->exists();
    }

    public function userCan(User $user, ClientAccount $clientAccount, ClientCapability|string $capability): bool
    {
        return $this->isActiveMember($user, $clientAccount)
            && $this->hasCapability($clientAccount, $capability);
    }

    public function userCanManage(User $user, ClientAccount $clientAccount, ClientCapability|string $capability): bool
    {
        return $this->canManageAccount($user, $clientAccount)
            && $this->hasCapability($clientAccount, $capability);
    }
}