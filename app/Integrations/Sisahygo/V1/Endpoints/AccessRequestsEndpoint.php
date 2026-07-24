<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\AccessRequestSubmissionRequest;
use App\Integrations\Sisahygo\V1\DTO\AccessRequestSubmissionResult;
use App\Integrations\Sisahygo\V1\Mappers\AccessRequestSubmissionMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class AccessRequestsEndpoint
{
    public function __construct(
        private readonly SisahygoApiClient $client,
        private readonly AccessRequestSubmissionMapper $mapper,
    ) {}

    public function create(AccessRequestSubmissionRequest $request, string $correlationId): AccessRequestSubmissionResult
    {
        $response = $this->client->postPublic('/access-requests', $request->toPayload(), $correlationId);
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Access request response is missing data object.');
        }

        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        return $this->mapper->map($data, $meta);
    }
}
