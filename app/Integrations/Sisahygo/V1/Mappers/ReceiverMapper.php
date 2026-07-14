<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\ReceiverSummary;

class ReceiverMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public function map(array $data): ReceiverSummary
    {
        $id = $data['customer_id'] ?? $data['id'] ?? null;
        $name = $data['name'] ?? $data['customer_name'] ?? null;

        if (! is_numeric($id) || ! is_string($name) || $name === '') {
            throw new SisahygoUnexpectedResponseException('Receiver response is missing required fields.');
        }

        return new ReceiverSummary((int) $id, $name, is_string($data['phone'] ?? null) ? $data['phone'] : null);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, ReceiverSummary>
     */
    public function mapList(array $items): array
    {
        return array_map(fn (array $item) => $this->map($item), $items);
    }
}