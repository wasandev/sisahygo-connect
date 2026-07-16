<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ReceiverSummary
{
    public function __construct(
        public int $customerId,
        public string $name,
        public ?string $phone = null,
        public ?int $branchRecId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'name' => $this->name,
            'phone' => $this->phone,
            'branch_rec_id' => $this->branchRecId,
        ];
    }
}
