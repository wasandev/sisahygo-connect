<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class OrderCheckingResult
{
    public function __construct(
        public ?int $id,
        public ?string $orderHeaderNo,
        public ?string $trackingNo,
        public ?string $orderStatus,
        public ?string $clientReferenceNo,
        public ?int $receiverCustomerId,
        public ?string $receiverName,
        public ?int $itemsCount,
        public ?string $submittedAt = null,
    ) {}

    public function statusLabel(): string
    {
        return $this->orderStatus === 'checking'
            ? __('order_checking.status.checking')
            : (string) $this->orderStatus;
    }

    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'order_header_no' => $this->orderHeaderNo,
            'tracking_no' => $this->trackingNo,
            'order_status' => $this->orderStatus,
            'status_label' => $this->statusLabel(),
            'client_reference_no' => $this->clientReferenceNo,
            'receiver_customer_id' => $this->receiverCustomerId,
            'receiver_name' => $this->receiverName,
            'items_count' => $this->itemsCount,
            'submitted_at' => $this->submittedAt,
        ];
    }
}
