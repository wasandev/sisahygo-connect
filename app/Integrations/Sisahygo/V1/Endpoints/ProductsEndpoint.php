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

        return $this->client->getMapped(
            $context,
            '/products',
            $query,
            function (array $response): array {
                $items = $response['data'] ?? null;

                if (! is_array($items)) {
                    throw new SisahygoUnexpectedResponseException('Products response is missing data list.', context: [
                        'response_domain' => 'reference_data',
                        'missing_fields' => ['data'],
                    ]);
                }

                return $this->mapper->mapList($items);
            },
            [
                'response_domain' => 'reference_data',
                'mapper_class' => ProductMapper::class,
                'dto_class' => ProductSummary::class,
            ],
        );
    }

    public function findAllowedPair(SisahygoIntegrationContext $context, int $productId, int $unitId): ?ProductSummary
    {
        return collect($this->search($context, productId: $productId))
            ->first(fn ($product) => $product->productId === $productId && $product->unitId === $unitId);
    }
}
