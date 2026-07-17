<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\ShipmentDetail;
use App\Integrations\Sisahygo\V1\DTO\ShipmentListResult;
use App\Integrations\Sisahygo\V1\Mappers\ShipmentMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ShipmentsEndpoint
{
    private const SUPPORTED_FILTERS = [
        'from_date',
        'to_date',
        'order_status',
        'tracking_no',
        'id',
        'order_header_no',
        'page',
        'per_page',
    ];

    public function __construct(private readonly SisahygoApiClient $client, private readonly ShipmentMapper $mapper) {}

    /** @param array<string, mixed> $filters */
    public function list(SisahygoIntegrationContext $context, array $filters = []): ShipmentListResult
    {
        $response = $this->client->get($context, '/shipments', $this->supportedFilters($filters));
        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Shipments response is missing data list.');
        }

        return $this->mapper->listResult($items, is_array($response['meta'] ?? null) ? $response['meta'] : null);
    }

    public function detail(SisahygoIntegrationContext $context, string $trackingNo): ShipmentDetail
    {
        $response = $this->client->get($context, '/shipments/'.rawurlencode($trackingNo));
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Shipment detail response is missing data.');
        }

        return $this->mapper->detail($data);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function supportedFilters(array $filters): array
    {
        return array_filter(
            array_intersect_key($filters, array_flip(self::SUPPORTED_FILTERS)),
            fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }
}
