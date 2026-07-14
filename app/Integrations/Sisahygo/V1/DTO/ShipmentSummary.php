<?php

namespace App\Integrations\Sisahygo\V1\DTO;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentType;
use Carbon\CarbonImmutable;

final readonly class ShipmentSummary
{
    public function __construct(
        public string $trackingNo,
        public ?string $orderHeaderNo,
        public ?CarbonImmutable $orderHeaderDate,
        public ?string $orderStatus,
        public ?string $orderType,
        public ?string $orderAmount,
        public ?PaymentType $paymentType,
        public ?PaymentStatus $paymentStatus,
        public ?int $senderCustomerId,
        public ?int $receiverCustomerId,
    ) {}
}