<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingItemData
{
    public function __construct(
        public int $productId,
        public int $unitId,
        public string $amount,
        public ?string $remark = null,
        public ?string $clientLineId = null,
        public ?string $clientItemNo = null,
        public ?string $clientProductCode = null,
    ) {}

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'product_id' => $this->productId,
            'unit_id' => $this->unitId,
            'amount' => $this->amount,
            'remark' => $this->remark,
            'client_line_id' => $this->clientLineId,
            'client_item_no' => $this->clientItemNo,
            'client_product_code' => $this->clientProductCode,
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
