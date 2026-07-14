<?php

namespace App\Domain\ClientAccount\Support;

use App\Domain\ClientAccount\Enums\CurrentClientAccountResolutionStatus;
use App\Domain\ClientAccount\Models\ClientAccount;

final readonly class CurrentClientAccountResolution
{
    public function __construct(
        public CurrentClientAccountResolutionStatus $status,
        public ?ClientAccount $clientAccount = null,
    ) {}

    public function hasSelectedAccount(): bool
    {
        return $this->status === CurrentClientAccountResolutionStatus::Selected
            && $this->clientAccount !== null;
    }
}
