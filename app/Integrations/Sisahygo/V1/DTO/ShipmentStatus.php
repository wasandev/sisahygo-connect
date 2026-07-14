<?php

namespace App\Integrations\Sisahygo\V1\DTO;

use Carbon\CarbonImmutable;

final readonly class ShipmentStatus
{
    public function __construct(public string $status, public ?CarbonImmutable $occurredAt = null, public ?string $description = null) {}
}