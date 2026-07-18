<?php

namespace App\Application\Dashboard;

use App\Application\History\ListOrderHistory;
use App\Application\Shipment\ShipmentQueryService;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Models\User;
use Carbon\CarbonImmutable;

class GetCustomerDashboard
{
    private const RECENT_LIMIT = 5;

    private const REQUEST_COUNT = 4;

    public function __construct(
        private readonly ShipmentQueryService $shipments,
        private readonly ListOrderHistory $history,
        private readonly ClientAccountAuthorizationService $authorization,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $user, ClientAccount $clientAccount): array
    {
        $ranges = $this->dateRanges();

        $latest = ($this->history)($user, $clientAccount, [
            'preset' => ListOrderHistory::PRESET_CUSTOM,
            'date_from' => $ranges['last_30_days']['date_from'],
            'date_to' => $ranges['last_30_days']['date_to'],
            'page' => 1,
            'per_page' => self::RECENT_LIMIT,
        ]);

        $today = $this->countFor($user, $clientAccount, [
            'date_from' => $ranges['today']['date_from'],
            'date_to' => $ranges['today']['date_to'],
        ]);

        $completed = $this->countFor($user, $clientAccount, [
            'date_from' => $ranges['last_30_days']['date_from'],
            'date_to' => $ranges['last_30_days']['date_to'],
            'status' => 'completed',
        ]);

        $attention = $this->shipments->list($user, $clientAccount, [
            'date_from' => $ranges['last_30_days']['date_from'],
            'date_to' => $ranges['last_30_days']['date_to'],
            'status' => 'problem',
            'page' => 1,
            'per_page' => self::RECENT_LIMIT,
        ]);

        return [
            'client_account' => [
                'id' => $clientAccount->id,
                'code' => $clientAccount->code,
                'name' => $clientAccount->name,
            ],
            'generated_at' => now(config('app.timezone'))->format('Y-m-d H:i'),
            'date_ranges' => $ranges,
            'request_count' => self::REQUEST_COUNT,
            'can_create_order' => $this->authorization->userCan($user, $clientAccount, ClientCapability::OrderCreate),
            'summary_cards' => [
                [
                    'key' => 'today',
                    'value' => $today,
                    'available' => true,
                    'range' => $ranges['today'],
                ],
                [
                    'key' => 'in_progress',
                    'value' => null,
                    'available' => false,
                    'range' => $ranges['last_30_days'],
                ],
                [
                    'key' => 'completed',
                    'value' => $completed,
                    'available' => true,
                    'range' => $ranges['last_30_days'],
                ],
                [
                    'key' => 'attention',
                    'value' => $this->metaTotal($attention['meta']),
                    'available' => true,
                    'range' => $ranges['last_30_days'],
                ],
            ],
            'latest_shipments' => array_slice($latest['items'], 0, self::RECENT_LIMIT),
            'attention_shipments' => array_slice($attention['items'], 0, self::RECENT_LIMIT),
            'recent_receivers' => $latest['recent_receivers'],
            'recent_products' => $latest['recent_products'],
        ];
    }

    /**
     * @return array<string, array{date_from: string, date_to: string}>
     */
    public function dateRanges(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today(config('app.timezone'));

        return [
            'today' => [
                'date_from' => $today->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            'last_30_days' => [
                'date_from' => $today->subDays(29)->toDateString(),
                'date_to' => $today->toDateString(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function countFor(User $user, ClientAccount $clientAccount, array $filters): int
    {
        $result = $this->shipments->list($user, $clientAccount, array_merge($filters, [
            'page' => 1,
            'per_page' => 1,
        ]));

        return $this->metaTotal($result['meta']);
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function metaTotal(?array $meta): int
    {
        return (int) ($meta['total'] ?? 0);
    }
}
