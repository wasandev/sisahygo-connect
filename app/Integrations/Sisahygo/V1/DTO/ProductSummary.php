<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ProductSummary
{
    public function __construct(
        public int $productId,
        public string $name,
        public int $unitId,
        public string $unitName,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->name,
            'unit_id' => $this->unitId,
            'unit_name' => $this->unitName,
        ];
    }
}
