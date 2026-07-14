<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ShipmentDetail
{
    /**
     * @param array<int, ShipmentItem> $items
     * @param array<int, ShipmentStatus> $statusHistory
     */
    public function __construct(public ShipmentSummary $summary, public array $items = [], public array $statusHistory = []) {}
}