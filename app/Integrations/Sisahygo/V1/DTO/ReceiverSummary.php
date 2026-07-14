<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ReceiverSummary
{
    public function __construct(public int $customerId, public string $name, public ?string $phone = null) {}
}