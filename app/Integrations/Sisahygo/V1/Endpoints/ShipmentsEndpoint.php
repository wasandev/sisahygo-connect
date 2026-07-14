<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\ShipmentDetail;
use App\Integrations\Sisahygo\V1\Mappers\ShipmentMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ShipmentsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly ShipmentMapper $mapper) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function list(SisahygoIntegrationContext $context, array $filters = []): array
    {
        $response = $this->client->get($context, '/shipments', array_merge($filters, [
            'sender_customer_ids' => $context->authorizedSenderCustomerIds,
            'receiver_customer_ids' => $context->authorizedReceiverCustomerIds,
        ]));

        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Shipments response is missing data list.');
        }

        return $this->mapper->summaryList($items);
    }

    public function detail(SisahygoIntegrationContext $context, string $trackingNo): ShipmentDetail
    {
        $response = $this->client->get($context, '/shipments/'.rawurlencode($trackingNo), [
            'sender_customer_ids' => $context->authorizedSenderCustomerIds,
            'receiver_customer_ids' => $context->authorizedReceiverCustomerIds,
        ]);

        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Shipment detail response is missing data.');
        }

        return $this->mapper->detail($data);
    }
}