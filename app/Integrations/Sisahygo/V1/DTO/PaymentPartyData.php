<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class PaymentPartyData
{
    public function __construct(
        public ?int $customerId = null,
        public ?string $name = null,
        public ?string $code = null,
        public ?string $branchName = null,
    ) {}
}
