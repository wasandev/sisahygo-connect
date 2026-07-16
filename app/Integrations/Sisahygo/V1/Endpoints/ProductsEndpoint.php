<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\ProductSummary;
use App\Integrations\Sisahygo\V1\Mappers\ProductMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ProductsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly ProductMapper $mapper) {}

    public function search(SisahygoIntegrationContext $context, ?string $search = null, ?int $productId = null): array
    {
        $query = array_filter([
            'search' => filled($search) ? $search : null,
            'product_id' => $productId,
        ], fn ($value): bool => $value !== null && $value !== '');

        $response = $this->client->get($context, '/products', $query);
        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Products response is missing data list.');
        }

        return $this->mapper->mapList($items);
    }

    public function findAllowedPair(SisahygoIntegrationContext $context, int $productId, int $unitId): ?ProductSummary
    {
        return collect($this->search($context, productId: $productId))
            ->first(fn ($product) => $product->productId === $productId && $product->unitId === $unitId);
    }
}
