<?php

namespace App\Domain\ClientAccount\Policies;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Models\User;

class ClientAccountPolicy
{
    public function __construct(private readonly ClientAccountAuthorizationService $authorization) {}

    public function view(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->isActiveMember($user, $clientAccount);
    }

    public function manageSettings(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCanManage($user, $clientAccount, ClientCapability::SettingsManage);
    }

    public function manageUsers(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCanManage($user, $clientAccount, ClientCapability::UsersManage);
    }
}