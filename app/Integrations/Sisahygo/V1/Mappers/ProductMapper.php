<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\ProductSummary;

class ProductMapper
{
    /** @param array<string, mixed> $data */
    public function map(array $data): ProductSummary
    {
        if (! is_numeric($data['product_id'] ?? null) || ! is_string($data['product_name'] ?? null) || $data['product_name'] === '' || ! is_numeric($data['unit_id'] ?? null) || ! is_string($data['unit_name'] ?? null) || $data['unit_name'] === '') {
            throw new SisahygoUnexpectedResponseException('Products response is missing required fields.');
        }

        return new ProductSummary(
            productId: (int) $data['product_id'],
            name: $data['product_name'],
            unitId: (int) $data['unit_id'],
            unitName: $data['unit_name'],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, ProductSummary>
     */
    public function mapList(array $items): array
    {
        return array_map(fn (array $item) => $this->map($item), $items);
    }
}
