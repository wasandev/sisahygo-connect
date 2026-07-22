<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingRequestData
{
    /**
     * @param  array<int, BulkOrderCheckingOrderData>  $orders
     */
    public function __construct(
        public ?string $batchReferenceNo,
        public ?string $batchDate,
        public array $orders,
    ) {}

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'batch_reference_no' => $this->batchReferenceNo,
            'batch_date' => $this->batchDate,
            'orders' => array_map(fn (BulkOrderCheckingOrderData $order): array => $order->toPayload(), $this->orders),
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
