<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ReportsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client) {}

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function report(SisahygoIntegrationContext $context, string $report, array $filters = []): array
    {
        $endpoint = match ($report) {
            'shipments' => '/reports/shipments',
            'order-checkings' => '/reports/order-checkings',
            'order-checking-items' => '/reports/order-checkings/items',
            'payments' => '/reports/payments',
            default => throw new SisahygoUnexpectedResponseException('Unsupported report.'),
        };

        $response = $this->client->get($context, $endpoint, $this->supportedFilters($filters));
        if (! is_array($response['data'] ?? null) || ! is_array($response['data']['rows'] ?? null) || ! is_array($response['data']['summary'] ?? null)) {
            throw new SisahygoUnexpectedResponseException('Report response is malformed.');
        }

        return $response;
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function supportedFilters(array $filters): array
    {
        return array_filter(array_intersect_key($filters, array_flip([
            'date_from', 'date_to', 'relationship', 'status', 'search', 'type', 'client_reference',
            'batch_reference', 'pricing_status', 'payment_status', 'payment_type', 'page', 'per_page', 'export',
        ])), fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
