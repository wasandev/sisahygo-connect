<?php

namespace App\Domain\ClientAccount\Policies;

use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Models\User;

class ClientAccountUserPolicy
{
    public function __construct(private readonly ClientAccountAuthorizationService $authorization) {}

    public function view(User $user, ClientAccountUser $membership): bool
    {
        return $this->authorization->isActiveMember($user, $membership->clientAccount);
    }

    public function manage(User $user, ClientAccountUser $membership): bool
    {
        return $this->authorization->canManageAccount($user, $membership->clientAccount);
    }
}