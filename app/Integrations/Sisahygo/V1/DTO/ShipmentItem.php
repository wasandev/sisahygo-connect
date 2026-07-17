<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ShipmentItem
{
    public function __construct(
        public ?int $id,
        public ?int $productId,
        public ?string $productName,
        public ?int $unitId,
        public ?string $unitName,
        public ?string $price,
        public ?string $amount,
        public ?string $lineAmount,
        public ?string $remark,
        public ?string $clientLineId = null,
        public ?string $clientItemNo = null,
        public ?string $clientProductCode = null,
    ) {}
}
