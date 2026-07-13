<?php

namespace App\Domain\Payment\Policies;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Models\User;

class PaymentPolicy
{
    public function __construct(private readonly ClientAccountAuthorizationService $authorization) {}

    public function viewAny(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCan($user, $clientAccount, ClientCapability::PaymentView);
    }

    public function download(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCan($user, $clientAccount, ClientCapability::PaymentDownload);
    }
}