<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\ClientPaymentData;
use App\Integrations\Sisahygo\V1\DTO\PaymentListQuery;
use App\Integrations\Sisahygo\V1\DTO\PaymentListResult;
use App\Integrations\Sisahygo\V1\Mappers\PaymentMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class PaymentsEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly PaymentMapper $mapper) {}

    public function list(SisahygoIntegrationContext $context, PaymentListQuery $query): PaymentListResult
    {
        $response = $this->client->get($context, '/payments', $query->toQuery());
        $items = $response['data'] ?? null;

        if (! is_array($items)) {
            throw new SisahygoUnexpectedResponseException('Payments response is missing data list.');
        }

        return $this->mapper->listResult(
            $items,
            is_array($response['summary'] ?? null) ? $response['summary'] : null,
            is_array($response['meta'] ?? null) ? $response['meta'] : null,
        );
    }

    public function detail(SisahygoIntegrationContext $context, string $paymentIdentifier): ClientPaymentData
    {
        $response = $this->client->get($context, '/payments/'.rawurlencode($paymentIdentifier));
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Payment detail response is missing data.');
        }

        return $this->mapper->payment($data);
    }
}
