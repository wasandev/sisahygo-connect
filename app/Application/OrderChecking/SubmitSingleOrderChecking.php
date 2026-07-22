<?php

namespace App\Application\OrderChecking;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingRequest;
use App\Integrations\Sisahygo\V1\DTO\OrderCheckingResult;
use App\Integrations\Sisahygo\V1\DTO\ProductSummary;
use App\Integrations\Sisahygo\V1\DTO\ReceiverSummary;
use App\Integrations\Sisahygo\V1\DTO\UnitSummary;
use App\Integrations\Sisahygo\V1\Endpoints\OrderCheckingsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ProductsEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\ReceiversEndpoint;
use App\Integrations\Sisahygo\V1\Endpoints\UnitsEndpoint;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmitSingleOrderChecking
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
     * @param  array<int, array<string, mixed>>  $items
     */
    public function submit(User $user, ClientAccount $clientAccount, ?int $selectedSenderCustomerId, int $receiverCustomerId, string $clientReferenceNo, ?string $remark, array $items): OrderCheckingResult
    {
        $lock = Cache::lock('order-checking-submit:'.$clientAccount->id.':'.$user->id.':'.$clientReferenceNo, 30);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'page' => __('order_checking.validation.submit_in_progress'),
            ]);
        }

        try {
            $context = $this->context($user, $clientAccount);
            $this->validateSenderSelection($clientAccount, $selectedSenderCustomerId, $context);
            $this->validateLocalPayload($receiverCustomerId, $clientReferenceNo, $remark, $items);

            $receiver = $this->receivers->findScoped($context, $receiverCustomerId);

            if (! $receiver) {
                throw ValidationException::withMessages([
                    'selectedReceiver.customer_id' => __('order_checking.validation.receiver_invalid'),
                ]);
            }

            $this->validateReferenceItems($context, $items);

            return $this->orderCheckings->create($context, new OrderCheckingRequest(
                clientReferenceNo: $clientReferenceNo,
                receiverCustomerId: $receiver->customerId,
                remark: filled($remark) ? $remark : null,
                items: $items,
            ));
        } finally {
            optional($lock)->release();
        }
    }

    public function reconcile(User $user, ClientAccount $clientAccount, string $clientReferenceNo): OrderCheckingResult
    {
        $context = $this->context($user, $clientAccount);

        Validator::make(['client_reference_no' => $clientReferenceNo], [
            'client_reference_no' => ['required', 'string', 'max:100'],
        ])->validate();

        return $this->orderCheckings->findByClientReference($context, $clientReferenceNo);
    }

    public function generatedClientReference(): string
    {
        return 'SC-'.now()->format('Ymd').'-'.strtoupper(str()->random(6));
    }

    private function context(User $user, ClientAccount $clientAccount): SisahygoIntegrationContext
    {
        return $this->contextBuilder->build($user, $clientAccount, ClientCapability::OrderCreate);
    }

    private function validateSenderSelection(ClientAccount $clientAccount, ?int $selectedSenderCustomerId, SisahygoIntegrationContext $context): void
    {
        $senders = $this->activeSenderLinks($clientAccount);

        if ($senders->isEmpty()) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('order_checking.validation.sender_unavailable'),
            ]);
        }

        $senderId = $selectedSenderCustomerId ?: ($senders->count() === 1 ? (int) $senders->first()->customer_id : null);

        if (! $senderId) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('order_checking.validation.sender_required'),
            ]);
        }

        if (! $senders->contains(fn (ClientAccountCustomer $link): bool => (int) $link->customer_id === $senderId)) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('order_checking.validation.sender_invalid'),
            ]);
        }

        if (! in_array($senderId, $context->authorizedSenderCustomerIds, true)) {
            throw ValidationException::withMessages([
                'selectedSenderCustomerId' => __('order_checking.validation.sender_invalid'),
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function validateLocalPayload(int $receiverCustomerId, string $clientReferenceNo, ?string $remark, array $items): void
    {
        Validator::make([
            'customer_rec_id' => $receiverCustomerId,
            'client_reference_no' => $clientReferenceNo,
            'remark' => $remark,
            'items' => $items,
        ], [
            'customer_rec_id' => ['required', 'integer'],
            'client_reference_no' => ['required', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['required', 'integer'],
            'items.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'items.*.remark' => ['nullable', 'string', 'max:200'],
            'items.*.client_line_id' => ['nullable', 'string', 'max:100'],
            'items.*.client_item_no' => ['nullable', 'string', 'max:100'],
            'items.*.client_product_code' => ['nullable', 'string', 'max:100'],
        ], __('order_checking.validation'))->validate();
    }

    /** @param array<int, array<string, mixed>> $items */
    private function validateReferenceItems(SisahygoIntegrationContext $context, array $items): void
    {
        $units = collect($this->units->list($context))->keyBy(fn (UnitSummary $unit): int => $unit->unitId);
        $errors = [];

        foreach ($items as $index => $item) {
            $productId = (int) Arr::get($item, 'product_id');
            $unitId = (int) Arr::get($item, 'unit_id');

            if (! $units->has($unitId)) {
                $errors["items.{$index}.unit_id"] = [__('order_checking.validation.unit_invalid')];

                continue;
            }

            if (! $this->products->findAllowedPair($context, $productId, $unitId)) {
                $errors["items.{$index}.product_id"] = [__('order_checking.validation.product_invalid')];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
