<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class PaymentListQuery
{
    public function __construct(
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public ?string $paymentStatus = null,
        public ?string $paymentType = null,
        public ?string $orderHeaderNo = null,
        public ?string $clientReferenceNo = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return array_filter([
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'payment_status' => $this->paymentStatus,
            'payment_type' => $this->paymentType,
            'order_header_no' => $this->orderHeaderNo,
            'client_reference_no' => $this->clientReferenceNo,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
