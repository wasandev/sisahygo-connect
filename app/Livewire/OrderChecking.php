<?php

namespace App\Livewire;

use App\Application\OrderChecking\SubmitSingleOrderChecking;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderChecking extends Component
{
    public string $state = 'editing';

    public array $senderOptions = [];

    public ?int $selectedSenderCustomerId = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public string $receiverSearch = '';

    public array $receiverResults = [];

    public ?array $selectedReceiver = null;

    public string $productSearch = '';

    public array $productResults = [];

    public array $units = [];

    public string $clientReferenceNo = '';

    public string $orderRemark = '';

    public array $items = [];

    public bool $isSubmitting = false;

    public ?string $pageError = null;

    public ?string $unknownMessage = null;

    public ?array $successResult = null;

    public ?array $submittedSummary = null;

    public function mount(SubmitSingleOrderChecking $orders): void
    {
        $this->clientReferenceNo = $orders->generatedClientReference();
        $this->items = [$this->blankItem()];

        try {
            $account = $this->currentClientAccount();
            $this->senderOptions = $orders->senderOptions($account);

            if ($this->senderOptions === []) {
                $this->unavailable = true;
                $this->unavailableMessage = __('order_checking.unavailable.no_sender');

                return;
            }

            if (count($this->senderOptions) === 1) {
                $this->selectedSenderCustomerId = (int) $this->senderOptions[0]['customer_id'];
            }

            $this->units = $orders->loadUnits(auth()->user(), $account);
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('order_checking.unavailable.no_credential');
        } catch (\Throwable) {
            $this->unavailable = true;
            $this->unavailableMessage = __('order_checking.unavailable.integration');
        }
    }

    public function updatedReceiverSearch(SubmitSingleOrderChecking $orders): void
    {
        $this->selectedReceiver = null;
        $this->receiverResults = [];

        if (mb_strlen(trim($this->receiverSearch)) < 2) {
            return;
        }

        try {
            $this->receiverResults = $orders->searchReceivers(auth()->user(), $this->currentClientAccount(), $this->receiverSearch);
            $this->clearPageError();
        } catch (SisahygoApiException) {
            $this->pageError = __('order_checking.errors.receiver_search_failed');
        }
    }

    public function selectReceiver(int $customerId): void
    {
        $receiver = collect($this->receiverResults)->firstWhere('customer_id', $customerId);

        if (! $receiver) {
            $this->addError('selectedReceiver.customer_id', __('order_checking.validation.receiver_invalid'));

            return;
        }

        $this->selectedReceiver = $receiver;
        $this->receiverSearch = $receiver['name'];
        $this->resetErrorBag('selectedReceiver.customer_id');
    }

    public function updatedProductSearch(SubmitSingleOrderChecking $orders): void
    {
        $this->productResults = [];

        if (mb_strlen(trim($this->productSearch)) < 2) {
            return;
        }

        try {
            $this->productResults = $orders->searchProducts(auth()->user(), $this->currentClientAccount(), $this->productSearch);
            $this->clearPageError();
        } catch (SisahygoApiException) {
            $this->pageError = __('order_checking.errors.product_search_failed');
        }
    }

    public function addProduct(int $productId, int $unitId): void
    {
        $product = collect($this->productResults)
            ->first(fn (array $item): bool => (int) $item['product_id'] === $productId && (int) $item['unit_id'] === $unitId);

        if (! $product) {
            return;
        }

        $selectedItem = [
            'product_id' => (int) $product['product_id'],
            'product_name' => $product['product_name'],
            'unit_id' => (int) $product['unit_id'],
            'unit_name' => $product['unit_name'],
        ];

        $blankIndex = collect($this->items)->search(
            fn (array $item): bool => blank(data_get($item, 'product_id'))
        );

        if ($blankIndex !== false) {
            $this->items[$blankIndex] = array_merge($this->items[$blankIndex], $selectedItem);

            return;
        }

        $this->items[] = $this->blankItem($selectedItem);
    }

    public function removeItem(string $rowKey): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn (array $item): bool => data_get($item, 'row_key') !== $rowKey
        ));

        if ($this->items === []) {
            $this->items = [$this->blankItem()];
        }
    }

    public function submit(SubmitSingleOrderChecking $orders): void
    {
        if ($this->isSubmitting || $this->unavailable) {
            return;
        }

        $this->isSubmitting = true;
        $this->pageError = null;
        $this->unknownMessage = null;
        $this->successResult = null;
        $this->resetErrorBag();

        try {
            if (! $this->selectedReceiver) {
                throw ValidationException::withMessages([
                    'selectedReceiver.customer_id' => __('order_checking.validation.selectedReceiver.customer_id.required'),
                ]);
            }

            $this->submittedSummary = $this->summary();

            $result = $orders->submit(
                user: auth()->user(),
                clientAccount: $this->currentClientAccount(),
                selectedSenderCustomerId: $this->selectedSenderCustomerId,
                receiverCustomerId: (int) $this->selectedReceiver['customer_id'],
                clientReferenceNo: $this->clientReferenceNo,
                remark: $this->orderRemark,
                items: $this->serviceItems(),
            );

            $this->successResult = $result->toSafeArray();
            $this->state = 'success';
        } catch (ValidationException $exception) {
            $this->state = 'api_validation_failed';
            $this->mapValidationErrors($exception->errors());
        } catch (SisahygoValidationException $exception) {
            $this->state = 'api_validation_failed';
            $this->mapApiValidationErrors($exception->safeContext()['validation_errors'] ?? []);
            $this->pageError ??= $this->safeApiMessage($exception);
        } catch (SisahygoConnectionException) {
            $this->state = 'unknown_result';
            $this->unknownMessage = __('order_checking.unknown.body');
        } catch (SisahygoApiException $exception) {
            $this->state = 'recoverable_failure';
            $this->pageError = $this->safeApiMessage($exception);
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function reconcile(SubmitSingleOrderChecking $orders): void
    {
        $this->pageError = null;

        try {
            $result = $orders->reconcile(auth()->user(), $this->currentClientAccount(), $this->clientReferenceNo);
            $this->successResult = $result->toSafeArray();
            $this->state = 'success';
            $this->unknownMessage = null;
        } catch (SisahygoNotFoundException) {
            $this->state = 'unknown_result';
            $this->unknownMessage = __('order_checking.unknown.not_found');
        } catch (SisahygoApiException $exception) {
            $this->pageError = $this->safeApiMessage($exception);
        }
    }

    public function createAnother(SubmitSingleOrderChecking $orders): void
    {
        $this->state = 'editing';
        $this->receiverSearch = '';
        $this->receiverResults = [];
        $this->selectedReceiver = null;
        $this->productSearch = '';
        $this->productResults = [];
        $this->clientReferenceNo = $orders->generatedClientReference();
        $this->orderRemark = '';
        $this->items = [$this->blankItem()];
        $this->pageError = null;
        $this->unknownMessage = null;
        $this->successResult = null;
        $this->submittedSummary = null;
        $this->resetErrorBag();
    }

    public function getReadyForReviewProperty(): bool
    {
        return ! $this->unavailable
            && (bool) $this->selectedReceiver
            && trim($this->clientReferenceNo) !== ''
            && collect($this->items)->contains(function ($item): bool {
                if (! is_array($item)) {
                    return false;
                }

                return filled(data_get($item, 'product_id'))
                    && filled(data_get($item, 'unit_id'))
                    && (float) data_get($item, 'amount', 0) >= 0.0001;
            });
    }

    public function render(): View
    {
        return view('livewire.order-checking')->layout('layouts.app', [
            'title' => __('navigation.order_checking'),
        ]);
    }

    private function currentClientAccount(): ClientAccount
    {
        if (app()->bound(ClientAccount::class)) {
            return app(ClientAccount::class);
        }

        $clientAccount = app(CurrentClientAccountResolver::class)->resolve(
            auth()->user(),
            session()->get(CurrentClientAccountResolver::SESSION_KEY),
        )->clientAccount;

        if ($clientAccount) {
            app()->instance(ClientAccount::class, $clientAccount);

            return $clientAccount;
        }

        return app(ClientAccount::class);
    }

    private function blankItem(array $overrides = []): array
    {
        return array_merge([
            'row_key' => (string) str()->uuid(),
            'product_id' => null,
            'product_name' => '',
            'unit_id' => null,
            'unit_name' => '',
            'amount' => 1,
            'remark' => '',
            'client_line_id' => '',
            'client_item_no' => '',
            'client_product_code' => '',
        ], $overrides);
    }

    private function serviceItems(): array
    {
        return array_values(array_map(fn (array $item): array => [
            'product_id' => $item['product_id'],
            'unit_id' => $item['unit_id'],
            'amount' => $item['amount'],
            'remark' => $item['remark'],
            'client_line_id' => $item['client_line_id'],
            'client_item_no' => $item['client_item_no'],
            'client_product_code' => $item['client_product_code'],
        ], $this->items));
    }

    private function summary(): array
    {
        return [
            'client_reference_no' => $this->clientReferenceNo,
            'receiver_name' => $this->selectedReceiver['name'] ?? null,
            'items_count' => count($this->items),
        ];
    }

    private function mapValidationErrors(array $errors): void
    {
        foreach ($errors as $field => $messages) {
            $this->addError($this->mapErrorField($field), $messages[0] ?? __('order_checking.errors.validation_failed'));
        }
    }

    private function mapApiValidationErrors(mixed $errors): void
    {
        if (! is_array($errors) || $errors === []) {
            $this->pageError = __('order_checking.errors.validation_failed');

            return;
        }

        $this->mapValidationErrors($errors);
    }

    private function mapErrorField(string $field): string
    {
        if ($field === 'customer_rec_id') {
            return 'selectedReceiver.customer_id';
        }

        if ($field === 'client_reference_no') {
            return 'clientReferenceNo';
        }

        if ($field === 'remark') {
            return 'orderRemark';
        }

        if (preg_match('/^items\.(\d+)\.(.+)$/', $field, $matches)) {
            $index = (int) $matches[1];
            $rowKey = $this->items[$index]['row_key'] ?? $index;

            return 'items.'.$rowKey.'.'.$matches[2];
        }

        return $field;
    }

    private function safeApiMessage(SisahygoApiException $exception): string
    {
        return match (true) {
            $exception instanceof SisahygoAuthenticationException => __('order_checking.errors.authentication'),
            $exception instanceof SisahygoAuthorizationException => __('order_checking.errors.authorization'),
            $exception instanceof SisahygoRateLimitException => __('order_checking.errors.rate_limited'),
            $exception instanceof SisahygoServerException => __('order_checking.errors.server', ['correlation' => $exception->safeContext()['correlation_id'] ?? '-']),
            default => __('order_checking.errors.recoverable'),
        };
    }

    private function clearPageError(): void
    {
        $this->pageError = null;
    }
}
