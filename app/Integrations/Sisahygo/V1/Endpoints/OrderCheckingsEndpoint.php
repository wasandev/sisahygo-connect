<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingRequestData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingResponseData;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingRequest;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingResult;
use App\Integrations\Sisahygo\V1\Mappers\BulkOrderCheckingMapper;
use App\Integrations\Sisahygo\V1\Mappers\OrderCheckingMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class OrderCheckingsEndpoint
{
    public function __construct(
        private readonly SisahygoApiClient $client,
        private readonly OrderCheckingMapper $mapper,
        private readonly BulkOrderCheckingMapper $bulkMapper,
    ) {}

    public function create(SisahygoIntegrationContext $context, OrderCheckingRequest $request): OrderCheckingResult
    {
        $response = $this->client->post($context, '/order-checkings', $request->toPayload());
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Order checking response is missing data object.');
        }

        return $this->mapper->map($data);
    }

    public function findByClientReference(SisahygoIntegrationContext $context, string $clientReferenceNo): OrderCheckingResult
    {
        $response = $this->client->get($context, '/order-checkings/'.rawurlencode($clientReferenceNo));
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Order checking lookup response is missing data object.');
        }

        return $this->mapper->map($data);
    }

    public function createBulk(SisahygoIntegrationContext $context, BulkOrderCheckingRequestData $request): BulkOrderCheckingResponseData
    {
        $response = $this->client->post($context, '/order-checkings/bulk', $request->toPayload());
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Bulk order checking response is missing data object.');
        }

        return $this->bulkMapper->map($data);
    }
}
