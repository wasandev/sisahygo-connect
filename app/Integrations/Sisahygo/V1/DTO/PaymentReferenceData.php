<?php

namespace App\Integrations\Sisahygo\V1\DTO;

use Carbon\CarbonImmutable;

final readonly class PaymentReferenceData
{
    public function __construct(
        public ?string $number = null,
        public ?CarbonImmutable $date = null,
    ) {}
}
