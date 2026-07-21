<?php

namespace App\Integrations\Sisahygo\V1\DTO;

use Carbon\CarbonImmutable;

final readonly class ClientPaymentData
{
    public function __construct(
        public string $paymentIdentifier,
        public ?string $source,
        public ?string $paymentType,
        public ?string $payerRole,
        public ?string $orderHeaderNo,
        public ?CarbonImmutable $orderHeaderDate,
        public ?string $clientReferenceNo,
        public ?CarbonImmutable $billingDate,
        public ?CarbonImmutable $paymentDate,
        public ?string $paymentStatus,
        public ?string $totalAmount,
        public ?string $paidAmount,
        public ?string $outstandingAmount,
        public ?string $discountAmount,
        public ?string $taxAmount,
        public PaymentReferenceData $invoice,
        public PaymentReferenceData $receipt,
        public PaymentPartyData $sender,
        public PaymentPartyData $receiver,
        public ?string $trackingReference,
    ) {}
}
