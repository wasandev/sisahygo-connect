<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\Mappers\ReceiverMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ReceiversEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly ReceiverMapper $mapper) {}

    public function list(SisahygoIntegrationContext $context): array
    {
        $response = $this->client->get($context, '/receivers', [
            'receiver_customer_ids' => $context->authorizedReceiverCustomerIds,
        ]);

        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Receivers response is missing data list.');
        }

        return $this->mapper->mapList($items);
    }
}