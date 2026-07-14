<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ShipmentItem
{
    public function __construct(public string $name, public int $quantity, public ?string $amount = null) {}
}