<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class PaymentListResult
{
    /** @param array<int, ClientPaymentData> $payments */
    public function __construct(
        public array $payments,
        public PaymentSummaryData $summary,
        public ?PaginationMeta $pagination = null,
    ) {}
}
