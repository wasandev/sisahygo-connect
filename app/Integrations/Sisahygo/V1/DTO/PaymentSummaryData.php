<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class PaymentSummaryData
{
    public function __construct(
        public int $recordCount = 0,
        public ?string $totalAmount = null,
        public int $paidRecordCount = 0,
        public int $outstandingRecordCount = 0,
    ) {}
}
