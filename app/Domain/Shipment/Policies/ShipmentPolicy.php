<?php

namespace App\Domain\Shipment\Policies;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Models\User;

class ShipmentPolicy
{
    public function __construct(private readonly ClientAccountAuthorizationService $authorization) {}

    public function viewAny(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCan($user, $clientAccount, ClientCapability::ShipmentView);
    }

    public function viewHistory(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCan($user, $clientAccount, ClientCapability::ShipmentHistory);
    }

    public function export(User $user, ClientAccount $clientAccount): bool
    {
        return $this->authorization->userCan($user, $clientAccount, ClientCapability::ShipmentExport);
    }
}