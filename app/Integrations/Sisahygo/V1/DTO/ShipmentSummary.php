<?php

namespace App\Integrations\Sisahygo\V1\DTO;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentType;
use Carbon\CarbonImmutable;

final readonly class ShipmentSummary
{
    /**
     * @param  array<int, ShipmentItem>  $items
     */
    public function __construct(
        public string $trackingNo,
        public ?int $id,
        public ?string $clientReferenceNo,
        public ?string $orderHeaderNo,
        public ?CarbonImmutable $orderHeaderDate,
        public ?string $orderStatus,
        public ?string $orderType,
        public ?string $orderAmount,
        public ?PaymentType $paymentType,
        public ?PaymentStatus $paymentStatus,
        public ?int $senderCustomerId,
        public ?int $receiverCustomerId,
        public ?string $branchName,
        public ?string $destinationBranchName,
        public ?string $senderName,
        public ?string $receiverName,
        public array $items = [],
    ) {}
}
