<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\UnitSummary;
use App\Integrations\Sisahygo\V1\Mappers\UnitMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class UnitsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly UnitMapper $mapper) {}

    public function list(SisahygoIntegrationContext $context): array
    {
        return $this->client->getMapped(
            $context,
            '/units',
            [],
            function (array $response): array {
                $items = $response['data'] ?? null;

                if (! is_array($items)) {
                    throw new SisahygoUnexpectedResponseException('Units response is missing data list.', context: [
                        'response_domain' => 'reference_data',
                        'missing_fields' => ['data'],
                    ]);
                }

                return $this->mapper->mapList($items);
            },
            [
                'response_domain' => 'reference_data',
                'mapper_class' => UnitMapper::class,
                'dto_class' => UnitSummary::class,
            ],
        );
    }
}
