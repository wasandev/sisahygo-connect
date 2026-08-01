<?php

namespace App\Integrations\Sisahygo\V1\Endpoints;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\V1\DTO\ReceiverSummary;
use App\Integrations\Sisahygo\V1\Mappers\ReceiverMapper;
use App\Integrations\Sisahygo\V1\SisahygoApiClient;

class ReceiversEndpoint
{
    public function __construct(private readonly SisahygoApiClient $client, private readonly ReceiverMapper $mapper) {}

    /** @return array<int, ReceiverSummary> */
    public function list(SisahygoIntegrationContext $context, ?string $search = null): array
    {
        return $this->client->getMapped(
            $context,
            '/receivers',
            array_filter([
                'search' => filled($search) ? $search : null,
            ], fn ($value): bool => $value !== null && $value !== ''),
            function (array $response): array {
                $items = $response['data'] ?? null;

                if (! is_array($items)) {
                    throw new SisahygoUnexpectedResponseException('Receivers response is missing data list.', context: [
                        'response_domain' => 'receiver',
                        'missing_fields' => ['data'],
                    ]);
                }

                return $this->mapper->mapList($items);
            },
            [
                'response_domain' => 'receiver',
                'mapper_class' => ReceiverMapper::class,
                'dto_class' => ReceiverSummary::class,
            ],
        );
    }

    public function findScoped(SisahygoIntegrationContext $context, int $receiverCustomerId): ?ReceiverSummary
    {
        return collect($this->list($context, (string) $receiverCustomerId))
            ->first(fn (ReceiverSummary $receiver): bool => $receiver->customerId === $receiverCustomerId);
    }
}
