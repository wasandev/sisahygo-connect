<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class OrderCheckingRequest
{
    /**
     * @param  array<int, array<string, mixed>>  $items
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
        return [
            'client_reference_no' => $this->clientReferenceNo,
            'customer_rec_id' => $this->receiverCustomerId,
            'remark' => $this->remark,
            'items' => array_map(fn (array $item): array => array_filter([
                'product_id' => (int) $item['product_id'],
                'unit_id' => (int) $item['unit_id'],
                'amount' => (float) $item['amount'],
                'remark' => $item['remark'] ?? null,
                'client_line_id' => $item['client_line_id'] ?? null,
                'client_item_no' => $item['client_item_no'] ?? null,
                'client_product_code' => $item['client_product_code'] ?? null,
            ], fn ($value): bool => $value !== null && $value !== ''), $this->items),
        ];
    }
}
