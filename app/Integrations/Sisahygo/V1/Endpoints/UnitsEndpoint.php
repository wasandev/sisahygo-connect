<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\Mappers\UnitMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class UnitsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly UnitMapper $mapper) {}

    public function list(SisahygoIntegrationContext $context): array
    {
        $response = $this->client->get($context, '/units');
        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Units response is missing data list.');
        }

        return $this->mapper->mapList($items);
    }
}
