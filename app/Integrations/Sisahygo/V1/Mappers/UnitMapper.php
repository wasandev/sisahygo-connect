<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\UnitSummary;

class UnitMapper
{
    /** @param array<string, mixed> $data */
    public function map(array $data): UnitSummary
    {
        $missingFields = [];

        if (! is_numeric($data['unit_id'] ?? null)) {
            $missingFields[] = 'unit_id';
        }

        if (! is_string($data['unit_name'] ?? null) || $data['unit_name'] === '') {
            $missingFields[] = 'unit_name';
        }

        if ($missingFields !== []) {
            throw new SisahygoUnexpectedResponseException('Units response is missing required fields.', context: [
                'response_domain' => 'reference_data',
                'missing_fields' => $missingFields,
            ]);
        }

        return new UnitSummary((int) $data['unit_id'], $data['unit_name']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, UnitSummary>
     */
    public function mapList(array $items): array
    {
        return array_map(fn (array $item) => $this->map($item), $items);
    }
}
