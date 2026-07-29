<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\InvitationActivationData;
use App\Integrations\Sisahygo\V1\DTO\InvitationPreviewData;
use App\Integrations\Sisahygo\V1\Mappers\InvitationMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class InvitationsEndpoint
{
    private const LOG_ENDPOINT = '/connect-onboarding/invitations/{token}';

    public function __construct(
        private readonly SisahygoApiClient $client,
        private readonly InvitationMapper $mapper,
    ) {}

    public function show(string $token): InvitationPreviewData
    {
        $response = $this->client->getPublic($this->path($token), logEndpoint: self::LOG_ENDPOINT);
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Invitation response is missing data object.');
        }

        return $this->mapper->preview($data);
    }

    /** @param array<string, mixed> $payload */
    public function activate(string $token, array $payload): InvitationActivationData
    {
        $response = $this->client->postPublic($this->path($token).'/activate', $payload, logEndpoint: self::LOG_ENDPOINT.'/activate');
        $data = $response['data'] ?? null;

        if (! is_array($data)) {
            throw new SisahygoUnexpectedResponseException('Invitation activation response is missing data object.');
        }

        return $this->mapper->activation($data);
    }

    private function path(string $token): string
    {
        return '/connect-onboarding/invitations/'.rawurlencode($token);
    }
}
