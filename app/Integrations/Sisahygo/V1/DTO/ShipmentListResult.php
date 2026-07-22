<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class ShipmentListResult
{
    /** @param array<int, ShipmentSummary> $items */
    public function __construct(public array $items, public ?PaginationMeta $meta = null) {}
}
