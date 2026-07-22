<?php

namespace App\Application\Search;

use App\Application\Shipment\ShipmentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ResolveUniversalSearch
{
    public function __construct(private readonly ShipmentQueryService $shipments) {}

    /**
     * @return array{found: bool, type: string|null, label: string|null, target_route: string|null, target_parameters: array<int, string>, query: string}
     */
    public function __invoke(User $user, ClientAccount $clientAccount, string $query): array
    {
        $validated = Validator::make(['query' => $query], [
            'query' => ['required', 'string', 'max:100'],
        ])->validate();

        $normalized = trim($validated['query']);

        foreach ($this->candidates($normalized) as $candidate) {
            $result = $this->shipments->list($user, $clientAccount, [
                $candidate['filter'] => $normalized,
                'page' => 1,
                'per_page' => 2,
            ]);

            $items = $result['items'];

            if ($items !== []) {
                $first = $items[0];

                return [
                    'found' => true,
                    'type' => $candidate['type'],
                    'label' => $candidate['label'],
                    'target_route' => 'orders.show',
                    'target_parameters' => [(string) $first['tracking_no']],
                    'query' => $normalized,
                ];
            }
        }

        return [
            'found' => false,
            'type' => null,
            'label' => null,
            'target_route' => null,
            'target_parameters' => [],
            'query' => $normalized,
        ];
    }

    /**
     * @return array<int, array{filter: string, type: string, label: string}>
     */
    private function candidates(string $query): array
    {
        $tracking = ['filter' => 'tracking_no', 'type' => 'tracking', 'label' => __('search.types.tracking')];

        if (ctype_digit($query)) {
            return [
                $tracking,
                ['filter' => 'order_header_no', 'type' => 'client_reference', 'label' => __('search.types.client_reference')],
                ['filter' => 'client_reference_no', 'type' => 'client_reference', 'label' => __('search.types.client_reference')],
                ['filter' => 'batch_reference_no', 'type' => 'batch_reference', 'label' => __('search.types.batch_reference')],
            ];
        }

        return [
            ['filter' => 'order_header_no', 'type' => 'client_reference', 'label' => __('search.types.client_reference')],
            ['filter' => 'client_reference_no', 'type' => 'client_reference', 'label' => __('search.types.client_reference')],
            ['filter' => 'batch_reference_no', 'type' => 'batch_reference', 'label' => __('search.types.batch_reference')],
            $tracking,
        ];
    }
}
