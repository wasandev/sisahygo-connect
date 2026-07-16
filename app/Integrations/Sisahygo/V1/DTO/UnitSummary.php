<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class UnitSummary
{
    public function __construct(public int $unitId, public string $name) {}

    public function toArray(): array
    {
        return [
            'unit_id' => $this->unitId,
            'unit_name' => $this->name,
        ];
    }
}
