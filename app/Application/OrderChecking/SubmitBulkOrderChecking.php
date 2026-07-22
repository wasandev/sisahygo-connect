<?php

namespace App\Application\OrderChecking;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingItemData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingOrderData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingRequestData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingResponseData;
use App\Integrations\Sisahygo\V1\DTO\ProductSummary;
use App\Integrations\Sisahygo\V1\DTO\ReceiverSummary;
use App\Integrations\Sisahygo\V1\DTO\UnitSummary;
use App\Integrations\Sisahygo\V1\Endpoints\OrderCheckingsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ProductsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ReceiversEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\UnitsEndpoint;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitBulkOrderChecking
{
    public function __construct(
        private readonly SisahygoIntegrationContextBuilder $contextBuilder,
        private readonly ReceiversEndpoint $receivers,
        private readonly ProductsEndpoint $products,
        private readonly UnitsEndpoint $units,
        private readonly OrderCheckingsEndpoint $orderCheckings,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function senderOptions(ClientAccount $clientAccount): array
    {
        return $this->activeSenderLinks($clientAccount)
            ->map(fn (ClientAccountCustomer $link): array => [
                'customer_id' => (int) $link->customer_id,
                'label' => 'Sender #'.(int) $link->customer_id,
                'is_default' => (bool) $link->is_default_sender,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function searchReceivers(User $user, ClientAccount $clientAccount, string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        return array_map(
            fn (ReceiverSummary $receiver): array => $receiver->toArray(),
            $this->receivers->list($this->context($user, $clientAccount), trim($search))
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function searchProducts(User $user, ClientAccount $clientAccount, string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        return array_map(
            fn (ProductSummary $product): array => $product->toArray(),
            $this->products->search($this->context($user, $clientAccount), trim($search))
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function loadUnits(User $user, ClientAccount $clientAccount): array
    {
        return array_map(
            fn (UnitSummary $unit): array => $unit->toArray(),
            $this->units->list($this->context($user, $clientAccount))
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submit(User $user, ClientAccount $clientAccount, ?int $selectedSenderCustomerId, array $payload): BulkOrderCheckingResponseData
    {
        $lock = Cache::lock('bulk-order-checking-submit:'.$clientAccount->id.':'.$user->id, 30);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'page' => __('bulk_order_checking.validation.submit_in_progress'),
            ]);
        }

        try {
            $context = $this->context($user, $clientAccount);
            $this->validateSenderSelection($clientAccount, $selectedSenderCustomerId, $context);
            $validated = $this->validatePayload($payload);

            return $this->orderCheckings->createBulk($context, $this->requestData($validated));
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     * @param  array<string, mixed>  $result
     * @return array<int, array<string, mixed>>
     */
    public function failedRetryOrders(array $orders, array $result): array
    {
        $failedIndexes = collect($result['results'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row) && ($row['status'] ?? null) === 'failed')
            ->map(fn (array $row): int => (int) ($row['index'] ?? -1))
            ->filter(fn (int $index): bool => array_key_exists($index, $orders))
            ->values()
            ->all();

        return array_values(array_map(fn (int $index): array => $orders[$index], $failedIndexes));
    }

    private function context(User $user, ClientAccount $clientAccount): SisahygoIntegrationContext
    {
        return $this->contextBuilder->build($user, $clientAccount, ClientCapability::OrderBulk);
    }

    private function validateSenderSelection(ClientAccount $clientAccount, ?int $selectedSenderCustomerId, SisahygoIntegrationContext $context): void
    {
        $senders = $this->activeSenderLinks($clientAccount);

        if ($senders->isEmpty()) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('bulk_order_checking.validation.sender_unavailable'),
            ]);
        }

        $senderId = $selectedSenderCustomerId ?: ($senders->count() === 1 ? (int) $senders->first()->customer_id : null);

        if (! $senderId) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('bulk_order_checking.validation.sender_required'),
            ]);
        }

        if (! $senders->contains(fn (ClientAccountCustomer $link): bool => (int) $link->customer_id === $senderId)) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('bulk_order_checking.validation.sender_invalid'),
            ]);
        }

        if (! in_array($senderId, $context->authorizedSenderCustomerIds, true)) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('bulk_order_checking.validation.sender_invalid'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validatePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'batch_reference_no' => ['nullable', 'string', 'max:100'],
            'batch_date' => ['nullable', 'date'],
            'orders' => ['required', 'array', 'min:1', 'max:50'],
            'orders.*.client_reference_no' => ['required', 'string', 'max:100', 'distinct'],
            'orders.*.customer_rec_id' => ['required', 'integer'],
            'orders.*.remark' => ['nullable', 'string', 'max:150'],
            'orders.*.items' => ['required', 'array', 'min:1', 'max:200'],
            'orders.*.items.*.product_id' => ['required', 'integer'],
            'orders.*.items.*.unit_id' => ['required', 'integer'],
            'orders.*.items.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'orders.*.items.*.remark' => ['nullable', 'string', 'max:200'],
            'orders.*.items.*.client_line_id' => ['nullable', 'string', 'max:100'],
            'orders.*.items.*.client_item_no' => ['nullable', 'string', 'max:100'],
            'orders.*.items.*.client_product_code' => ['nullable', 'string', 'max:100'],
        ], __('bulk_order_checking.validation'));

        $validator->after(function ($validator): void {
            $references = collect($validator->getData()['orders'] ?? [])
                ->map(fn (mixed $order): string => is_array($order) ? trim((string) ($order['client_reference_no'] ?? '')) : '')
                ->filter()
                ->duplicates();

            if ($references->isEmpty()) {
                return;
            }

            foreach (($validator->getData()['orders'] ?? []) as $index => $order) {
                if (is_array($order) && $references->contains(trim((string) ($order['client_reference_no'] ?? '')))) {
                    $validator->errors()->add("orders.{$index}.client_reference_no", __('bulk_order_checking.validation.duplicate_reference'));
                }
            }
        });

        return $validator->validate();
    }

    /** @param array<string, mixed> $validated */
    private function requestData(array $validated): BulkOrderCheckingRequestData
    {
        return new BulkOrderCheckingRequestData(
            batchReferenceNo: filled($validated['batch_reference_no'] ?? null) ? (string) $validated['batch_reference_no'] : null,
            batchDate: filled($validated['batch_date'] ?? null) ? (string) $validated['batch_date'] : null,
            orders: array_map(fn (array $order): BulkOrderCheckingOrderData => new BulkOrderCheckingOrderData(
                clientReferenceNo: (string) $order['client_reference_no'],
                receiverCustomerId: (int) $order['customer_rec_id'],
                remark: filled($order['remark'] ?? null) ? (string) $order['remark'] : null,
                items: array_map(fn (array $item): BulkOrderCheckingItemData => new BulkOrderCheckingItemData(
                    productId: (int) $item['product_id'],
                    unitId: (int) $item['unit_id'],
                    amount: (string) $item['amount'],
                    remark: filled($item['remark'] ?? null) ? (string) $item['remark'] : null,
                    clientLineId: filled($item['client_line_id'] ?? null) ? (string) $item['client_line_id'] : null,
                    clientItemNo: filled($item['client_item_no'] ?? null) ? (string) $item['client_item_no'] : null,
                    clientProductCode: filled($item['client_product_code'] ?? null) ? (string) $item['client_product_code'] : null,
                ), $order['items']),
            ), $validated['orders']),
        );
    }

    private function activeSenderLinks(ClientAccount $clientAccount): Collection
    {
        return $clientAccount->customerLinks()
            ->where('is_active', true)
            ->where('can_send', true)
            ->orderByDesc('is_default_sender')
            ->orderBy('id')
            ->get();
    }
}
