<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\ReceiverSummary;

class ReceiverMapper
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function map(array $data): ReceiverSummary
    {
        $id = $data['customer_rec_id'] ?? $data['customer_id'] ?? $data['id'] ?? null;
        $name = $data['to_customer_name'] ?? $data['name'] ?? $data['customer_name'] ?? null;
        $phone = $data['to_customer_phone'] ?? $data['phone'] ?? null;
        $branchRecId = $data['branch_rec_id'] ?? null;

        if (! is_numeric($id) || ! is_string($name) || $name === '') {
            throw new SisahygoUnexpectedResponseException('Receiver response is missing required fields.');
        }

        return new ReceiverSummary(
            customerId: (int) $id,
            name: $name,
            phone: is_string($phone) ? $phone : null,
            branchRecId: is_numeric($branchRecId) ? (int) $branchRecId : null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ReceiverSummary>
     */
    public function mapList(array $items): array
    {
        return array_map(fn (array $item) => $this->map($item), $items);
    }
}
