<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingOrderData
{
    /**
     * @param  array<int, BulkOrderCheckingItemData>  $items
     */
    public function __construct(
        public string $clientReferenceNo,
        public int $receiverCustomerId,
        public ?string $remark,
        public array $items,
    ) {}

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'client_reference_no' => $this->clientReferenceNo,
            'customer_rec_id' => $this->receiverCustomerId,
            'remark' => $this->remark,
            'items' => array_map(fn (BulkOrderCheckingItemData $item): array => $item->toPayload(), $this->items),
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
