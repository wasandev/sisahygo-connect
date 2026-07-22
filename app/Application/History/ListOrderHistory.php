<?php

namespace App\Application\History;

use App\Application\Shipment\ShipmentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class ListOrderHistory
{
    public const PRESET_TODAY = 'today';

    public const PRESET_LAST_7_DAYS = 'last_7_days';

    public const PRESET_LAST_30_DAYS = 'last_30_days';

    public const PRESET_CURRENT_MONTH = 'current_month';

    public const PRESET_CUSTOM = 'custom';

    public function __construct(private readonly ShipmentQueryService $shipments) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>|null, filters: array<string, mixed>, recent_receivers: array<int, array<string, mixed>>, recent_products: array<int, array<string, mixed>>}
     */
    public function __invoke(User $user, ClientAccount $clientAccount, array $filters = []): array
    {
        $normalized = $this->normalize($filters);
        $result = $this->shipments->list($user, $clientAccount, $normalized);

        return [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'filters' => $normalized,
            'recent_receivers' => $this->recentReceivers($result['items']),
            'recent_products' => $this->recentProducts($result['items']),
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today(config('app.timezone'));

        return [
            'preset' => self::PRESET_LAST_30_DAYS,
            'date_from' => $today->subDays(29)->toDateString(),
            'date_to' => $today->toDateString(),
            'status' => null,
            'keyword' => null,
            'page' => 1,
            'per_page' => 15,
        ];
    }

    /** @return array{date_from: string, date_to: string} */
    public function datesForPreset(string $preset, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today(config('app.timezone'));

        return match ($preset) {
            self::PRESET_TODAY => [
                'date_from' => $today->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            self::PRESET_LAST_7_DAYS => [
                'date_from' => $today->subDays(6)->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            self::PRESET_CURRENT_MONTH => [
                'date_from' => $today->startOfMonth()->toDateString(),
                'date_to' => $today->toDateString(),
            ],
            default => [
                'date_from' => $today->subDays(29)->toDateString(),
                'date_to' => $today->toDateString(),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalize(array $filters): array
    {
        $defaults = $this->defaults();
        $data = array_merge($defaults, $filters);
        $preset = (string) ($data['preset'] ?? self::PRESET_LAST_30_DAYS);

        if ($preset !== self::PRESET_CUSTOM) {
            $data = array_merge($data, $this->datesForPreset($preset));
        }

        $validated = Validator::make($data, [
            'preset' => ['nullable', 'string', 'in:today,last_7_days,last_30_days,current_month,custom'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'date_to.after_or_equal' => __('history.validation.date_range'),
        ])->validate();

        return [
            'preset' => $validated['preset'] ?? self::PRESET_LAST_30_DAYS,
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'status' => $validated['status'] ?? null,
            'keyword' => $validated['keyword'] ?? null,
            'page' => isset($validated['page']) ? (int) $validated['page'] : 1,
            'per_page' => isset($validated['per_page']) ? (int) $validated['per_page'] : 15,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function recentReceivers(array $items, int $limit = 5): array
    {
        $receivers = [];

        foreach ($items as $item) {
            $name = trim((string) ($item['receiver_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $key = $item['receiver_customer_id'] ?: mb_strtolower($name);
            $date = $item['order_header_date'] ?? null;

            if (! isset($receivers[$key])) {
                $receivers[$key] = [
                    'receiver_customer_id' => $item['receiver_customer_id'] ?? null,
                    'name' => $name,
                    'latest_order_date' => $date,
                    'count' => 0,
                ];
            }

            $receivers[$key]['count']++;
            if ($date && (($receivers[$key]['latest_order_date'] ?? null) === null || strcmp($date, $receivers[$key]['latest_order_date']) > 0)) {
                $receivers[$key]['latest_order_date'] = $date;
            }
        }

        usort($receivers, fn (array $a, array $b): int => strcmp((string) ($b['latest_order_date'] ?? ''), (string) ($a['latest_order_date'] ?? '')));

        return array_slice(array_values($receivers), 0, $limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function recentProducts(array $items, int $limit = 5): array
    {
        $products = [];

        foreach ($items as $shipment) {
            foreach (($shipment['items'] ?? []) as $item) {
                $name = trim((string) ($item['product_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $key = $item['product_id'] ?: mb_strtolower($name.'|'.($item['unit_name'] ?? ''));
                $date = $shipment['order_header_date'] ?? null;

                if (! isset($products[$key])) {
                    $products[$key] = [
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $name,
                        'unit_name' => $item['unit_name'] ?? null,
                        'latest_order_date' => $date,
                        'count' => 0,
                    ];
                }

                $products[$key]['count']++;
                if ($date && (($products[$key]['latest_order_date'] ?? null) === null || strcmp($date, $products[$key]['latest_order_date']) > 0)) {
                    $products[$key]['latest_order_date'] = $date;
                }
            }
        }

        usort($products, fn (array $a, array $b): int => strcmp((string) ($b['latest_order_date'] ?? ''), (string) ($a['latest_order_date'] ?? '')));

        return array_slice(array_values($products), 0, $limit);
    }
}
