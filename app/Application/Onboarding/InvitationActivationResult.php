<?php

namespace App\Application\Onboarding;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;

final readonly class InvitationActivationResult
{
    public function __construct(
        public User $user,
        public ClientAccount $clientAccount,
    ) {}
}
