<?php

namespace App\Livewire;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\OrderChecking\SubmitBulkOrderChecking;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderCheckingBulk extends Component
{
    public string $step = 'edit';

    public string $state = 'editing';

    public array $senderOptions = [];

    public ?int $selectedSenderCustomerId = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public string $batchReferenceNo = '';

    public string $batchDate = '';

    public array $orders = [];

    public array $units = [];

    public string $receiverSearch = '';

    public array $receiverResults = [];

    public string $productSearch = '';

    public array $productResults = [];

    public ?string $activeOrderKey = null;

    public ?string $activeItemKey = null;

    public string $resultFilter = 'all';

    public bool $isSubmitting = false;

    public bool $dirty = false;

    public ?string $pageError = null;

    public ?string $notice = null;

    public ?string $unknownMessage = null;

    public ?array $processedResult = null;

    public array $submittedOrders = [];

    public array $validationSummary = [];

    public ?string $retryBanner = null;

    public function mount(SubmitBulkOrderChecking $bulk): void
    {
        $order = $this->blankOrder();
        $this->orders = [$order];
        $this->activeOrderKey = $order['row_key'];
        $this->activeItemKey = $order['items'][0]['row_key'];
        $this->batchDate = now()->toDateString();

        try {
            $account = $this->currentClientAccount();
            $this->senderOptions = $bulk->senderOptions($account);

            if ($this->senderOptions === []) {
                $this->unavailable = true;
                $this->unavailableMessage = __('bulk_order_checking.unavailable.no_sender');

                return;
            }

            if (count($this->senderOptions) === 1) {
                $this->selectedSenderCustomerId = (int) $this->senderOptions[0]['customer_id'];
            }

            $this->units = $bulk->loadUnits(auth()->user(), $account);
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('bulk_order_checking.unavailable.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('bulk_order_checking.errors.authorization');
        } catch (\Throwable) {
            $this->unavailable = true;
            $this->unavailableMessage = __('bulk_order_checking.unavailable.integration');
        }
    }

    public function updated($name): void
    {
        if (str_starts_with((string) $name, 'orders') || in_array($name, ['batchReferenceNo', 'batchDate'], true)) {
            $this->dirty = $this->hasMeaningfulData();
            $this->validationSummary = [];
            $this->pageError = null;
        }
    }

    public function selectOrder(string $orderKey): void
    {
        if ($this->orderIndex($orderKey) === null) {
            return;
        }

        $this->activeOrderKey = $orderKey;
        $this->activeItemKey = $this->orders[$this->orderIndex($orderKey)]['items'][0]['row_key'] ?? null;
        $this->receiverResults = [];
        $this->productResults = [];
    }

    public function previousOrder(): void
    {
        $index = $this->activeOrderIndex();

        if ($index !== null && $index > 0) {
            $this->selectOrder($this->orders[$index - 1]['row_key']);
        }
    }

    public function nextOrder(): void
    {
        $index = $this->activeOrderIndex();

        if ($index !== null && $index < count($this->orders) - 1) {
            $this->selectOrder($this->orders[$index + 1]['row_key']);
        }
    }

    public function addOrder(): void
    {
        if (count($this->orders) >= 50) {
            $this->addError('orders', __('bulk_order_checking.validation.orders.max'));

            return;
        }

        $order = $this->blankOrder();
        $this->orders[] = $order;
        $this->dirty = true;
        $this->notice = null;
        $this->selectOrder($order['row_key']);
    }

    public function duplicateActiveOrder(): void
    {
        $index = $this->activeOrderIndex();

        if ($index === null || count($this->orders) >= 50) {
            return;
        }

        $source = $this->orders[$index];
        $duplicate = $this->blankOrder([
            'customer_rec_id' => $source['customer_rec_id'] ?? null,
            'selected_receiver' => $source['selected_receiver'] ?? null,
            'remark' => $source['remark'] ?? '',
            'items' => array_map(fn (array $item): array => $this->blankItem([
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? '',
                'unit_id' => $item['unit_id'] ?? null,
                'unit_name' => $item['unit_name'] ?? '',
                'amount' => (string) ($item['amount'] ?? '1'),
                'remark' => $item['remark'] ?? '',
                'client_product_code' => $item['client_product_code'] ?? '',
                'client_line_id' => '',
                'client_item_no' => '',
            ]), $source['items'] ?? []),
        ]);

        array_splice($this->orders, $index + 1, 0, [$duplicate]);
        $this->dirty = true;
        $this->notice = __('bulk_order_checking.notice.duplicated');
        $this->selectOrder($duplicate['row_key']);
    }

    public function removeActiveOrder(): void
    {
        $index = $this->activeOrderIndex();

        if ($index === null || count($this->orders) <= 1) {
            return;
        }

        array_splice($this->orders, $index, 1);
        $nextIndex = min($index, count($this->orders) - 1);
        $this->dirty = true;
        $this->selectOrder($this->orders[$nextIndex]['row_key']);
    }

    public function removeOrder(string $rowKey): void
    {
        $this->selectOrder($rowKey);
        $this->removeActiveOrder();
    }

    public function addItem(?string $orderKey = null): void
    {
        if ($this->step !== 'edit') {
            return;
        }

        $orderKey ??= $this->activeOrderKey;
        $orderIndex = $orderKey ? $this->orderIndex($orderKey) : null;

        if ($orderIndex === null) {
            return;
        }

        if (count($this->orders[$orderIndex]['items']) >= 200) {
            $this->addError("orders.{$orderKey}.items", __('bulk_order_checking.validation.orders.*.items.max'));

            return;
        }

        $item = $this->blankItem();
        $this->orders[$orderIndex]['items'][] = $item;
        $this->activeItemKey = $item['row_key'];
        $this->dirty = true;
    }

    public function removeItem(string $orderKey, string $itemKey): void
    {
        $orderIndex = $this->orderIndex($orderKey);

        if ($orderIndex === null) {
            return;
        }

        $items = array_values(array_filter(
            $this->orders[$orderIndex]['items'],
            fn (array $item): bool => $item['row_key'] !== $itemKey
        ));

        $this->orders[$orderIndex]['items'] = $items === [] ? [$this->blankItem()] : $items;
        $this->activeItemKey = $this->orders[$orderIndex]['items'][0]['row_key'] ?? null;
        $this->dirty = true;
    }

    public function setActiveItem(string $itemKey): void
    {
        $index = $this->activeOrderIndex();

        if ($index !== null && $this->itemIndex($index, $itemKey) !== null) {
            $this->activeItemKey = $itemKey;
        }
    }

    public function clearReceiver(): void
    {
        $index = $this->activeOrderIndex();

        if ($index === null) {
            return;
        }

        $this->orders[$index]['selected_receiver'] = null;
        $this->orders[$index]['customer_rec_id'] = null;
        $this->receiverSearch = '';
        $this->receiverResults = [];
        $this->dirty = true;
    }

    public function updatedReceiverSearch(SubmitBulkOrderChecking $bulk): void
    {
        $this->receiverResults = [];

        if (mb_strlen(trim($this->receiverSearch)) < 2) {
            return;
        }

        try {
            $this->receiverResults = $bulk->searchReceivers(auth()->user(), $this->currentClientAccount(), $this->receiverSearch);
            $this->pageError = null;
        } catch (SisahygoApiException) {
            $this->pageError = __('bulk_order_checking.errors.receiver_search_failed');
        }
    }

    public function selectReceiver(?string $orderKey, int $customerId): void
    {
        $orderKey ??= $this->activeOrderKey;
        $orderIndex = $orderKey ? $this->orderIndex($orderKey) : null;
        $receiver = collect($this->receiverResults)->firstWhere('customer_id', $customerId);

        if ($orderIndex === null || ! $receiver) {
            return;
        }

        $this->orders[$orderIndex]['selected_receiver'] = $receiver;
        $this->orders[$orderIndex]['customer_rec_id'] = (int) $receiver['customer_id'];
        $this->receiverSearch = '';
        $this->receiverResults = [];
        $this->dirty = true;
        $this->resetErrorBag("orders.{$orderKey}.customer_rec_id");
    }

    public function updatedProductSearch(SubmitBulkOrderChecking $bulk): void
    {
        $this->productResults = [];

        if (mb_strlen(trim($this->productSearch)) < 2) {
            return;
        }

        try {
            $this->productResults = $bulk->searchProducts(auth()->user(), $this->currentClientAccount(), $this->productSearch);
            $this->pageError = null;
        } catch (SisahygoApiException) {
            $this->pageError = __('bulk_order_checking.errors.product_search_failed');
        }
    }

    public function selectProduct(?string $orderKey, ?string $itemKey, int $productId, int $unitId): void
    {
        $orderKey ??= $this->activeOrderKey;
        $itemKey ??= $this->activeItemKey;
        $orderIndex = $orderKey ? $this->orderIndex($orderKey) : null;
        $itemIndex = $orderIndex === null || ! $itemKey ? null : $this->itemIndex($orderIndex, $itemKey);
        $product = collect($this->productResults)->first(fn (array $item): bool => (int) $item['product_id'] === $productId && (int) $item['unit_id'] === $unitId);

        if ($orderIndex === null || $itemIndex === null || ! $product) {
            return;
        }

        $this->orders[$orderIndex]['items'][$itemIndex]['product_id'] = (int) $product['product_id'];
        $this->orders[$orderIndex]['items'][$itemIndex]['product_name'] = $product['product_name'];
        $this->orders[$orderIndex]['items'][$itemIndex]['unit_id'] = (int) $product['unit_id'];
        $this->orders[$orderIndex]['items'][$itemIndex]['unit_name'] = $product['unit_name'];
        $this->productSearch = '';
        $this->productResults = [];
        $this->dirty = true;
    }

    public function clearProduct(string $itemKey): void
    {
        $orderIndex = $this->activeOrderIndex();
        $itemIndex = $orderIndex === null ? null : $this->itemIndex($orderIndex, $itemKey);

        if ($orderIndex === null || $itemIndex === null) {
            return;
        }

        $this->orders[$orderIndex]['items'][$itemIndex]['product_id'] = null;
        $this->orders[$orderIndex]['items'][$itemIndex]['product_name'] = '';
        $this->orders[$orderIndex]['items'][$itemIndex]['unit_id'] = null;
        $this->orders[$orderIndex]['items'][$itemIndex]['unit_name'] = '';
        $this->dirty = true;
    }

    public function beginReview(): void
    {
        if (! $this->validateForReview()) {
            $this->step = 'edit';
            $this->state = 'request_rejected';
            $this->pageError = __('bulk_order_checking.errors.local_validation');
            $this->activateFirstValidationOrder();

            return;
        }

        $this->step = 'review';
        $this->state = 'review';
        $this->pageError = null;
        $this->notice = null;
    }

    public function backToEdit(?string $orderKey = null): void
    {
        $this->step = 'edit';
        $this->state = 'editing';

        if ($orderKey) {
            $this->selectOrder($orderKey);
        }
    }

    public function submit(SubmitBulkOrderChecking $bulk): void
    {
        if ($this->step !== 'review') {
            $this->beginReview();

            return;
        }

        $this->confirmSubmit($bulk);
    }

    public function confirmSubmit(SubmitBulkOrderChecking $bulk): void
    {
        if ($this->isSubmitting || $this->unavailable || $this->step !== 'review') {
            return;
        }

        if (! $this->validateForReview()) {
            $this->backToEdit();
            $this->pageError = __('bulk_order_checking.errors.local_validation');
            $this->activateFirstValidationOrder();

            return;
        }

        $this->isSubmitting = true;
        $this->pageError = null;
        $this->unknownMessage = null;
        $this->processedResult = null;
        $this->resetErrorBag();

        try {
            $this->submittedOrders = $this->serviceOrders();
            $result = $bulk->submit(auth()->user(), $this->currentClientAccount(), $this->selectedSenderCustomerId, [
                'batch_reference_no' => $this->batchReferenceNo,
                'batch_date' => $this->batchDate,
                'orders' => $this->submittedOrders,
            ]);

            $this->processedResult = $result->toSafeArray();
            $this->resultFilter = 'all';
            $this->step = 'result';
            $this->state = $this->processedResult['outcome'];
            $this->dirty = false;
        } catch (ValidationException $exception) {
            $this->step = 'edit';
            $this->state = 'request_rejected';
            $this->mapValidationErrors($exception->errors());
            $this->pageError ??= __('bulk_order_checking.errors.local_validation');
            $this->activateFirstValidationOrder();
        } catch (SisahygoValidationException $exception) {
            $this->step = 'edit';
            $this->state = 'request_rejected';
            $this->mapValidationErrors($exception->safeContext()['validation_errors'] ?? []);
            $this->pageError = __('bulk_order_checking.errors.request_rejected');
            $this->activateFirstValidationOrder();
        } catch (SisahygoConnectionException) {
            $this->step = 'edit';
            $this->state = 'unknown_result';
            $this->unknownMessage = __('bulk_order_checking.unknown.body');
        } catch (SisahygoApiException $exception) {
            $this->step = 'edit';
            $this->state = 'recoverable_failure';
            $this->pageError = $this->safeApiMessage($exception);
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function prepareFailedRetry(SubmitBulkOrderChecking $bulk): void
    {
        if (! $this->processedResult) {
            return;
        }

        $retryOrders = $bulk->failedRetryOrders($this->submittedOrders, $this->processedResult);

        if ($retryOrders === []) {
            return;
        }

        $this->orders = array_map(fn (array $order): array => $this->uiOrderFromServiceOrder($order), $retryOrders);
        $this->step = 'edit';
        $this->state = 'editing';
        $this->resultFilter = 'all';
        $this->processedResult = null;
        $this->retryBanner = __('bulk_order_checking.results.retry_banner', ['count' => count($retryOrders)]);
        $this->pageError = null;
        $this->dirty = true;
        $this->resetErrorBag();
        $this->selectOrder($this->orders[0]['row_key']);
    }

    public function createAnother(): void
    {
        $order = $this->blankOrder();
        $this->step = 'edit';
        $this->state = 'editing';
        $this->batchReferenceNo = '';
        $this->batchDate = now()->toDateString();
        $this->orders = [$order];
        $this->activeOrderKey = $order['row_key'];
        $this->activeItemKey = $order['items'][0]['row_key'];
        $this->submittedOrders = [];
        $this->processedResult = null;
        $this->pageError = null;
        $this->notice = null;
        $this->unknownMessage = null;
        $this->retryBanner = null;
        $this->validationSummary = [];
        $this->dirty = false;
        $this->resetErrorBag();
    }

    public function jumpToValidation(string $orderKey): void
    {
        $this->backToEdit($orderKey);
    }

    public function getReviewSummaryProperty(): array
    {
        $statuses = $this->orderStatuses();

        return [
            'orders' => count($this->orders),
            'items' => collect($this->orders)->sum(fn (array $order): int => count($order['items'] ?? [])),
            'complete' => collect($statuses)->where('status', 'complete')->count(),
            'incomplete' => collect($statuses)->where('status', 'incomplete')->count(),
            'errors' => collect($statuses)->where('status', 'error')->count(),
            'missing' => collect($statuses)->whereIn('status', ['incomplete', 'error'])->count(),
        ];
    }

    public function getActiveOrderProperty(): ?array
    {
        $index = $this->activeOrderIndex();

        return $index === null ? null : $this->orders[$index];
    }

    public function getActiveOrderPositionProperty(): int
    {
        $index = $this->activeOrderIndex();

        return $index === null ? 1 : $index + 1;
    }

    public function getOrderStatusesProperty(): array
    {
        return $this->orderStatuses();
    }

    public function getFilteredResultRowsProperty(): array
    {
        $rows = $this->processedResult['results'] ?? [];

        if ($this->resultFilter === 'success') {
            return array_values(array_filter($rows, fn (array $row): bool => ($row['status'] ?? null) === 'success'));
        }

        if ($this->resultFilter === 'failed') {
            return array_values(array_filter($rows, fn (array $row): bool => ($row['status'] ?? null) === 'failed'));
        }

        return $rows;
    }

    public function getVisibleResultTsvProperty(): string
    {
        $lines = [__('bulk_order_checking.copy.tsv_header')];

        foreach ($this->filteredResultRows as $row) {
            $lines[] = implode("\t", [
                $row['client_reference_no'] ?? '',
                $row['tracking_no'] ?? '',
                $row['status'] ?? '',
            ]);
        }

        return implode("\n", $lines);
    }

    public function render(): View
    {
        return view('livewire.order-checking-bulk')->layout('layouts.app', [
            'title' => __('bulk_order_checking.title'),
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

    private function blankOrder(array $overrides = []): array
    {
        return array_merge([
            'row_key' => (string) str()->uuid(),
            'client_reference_no' => $this->generateClientReferenceNo(),
            'customer_rec_id' => null,
            'selected_receiver' => null,
            'remark' => '',
            'items' => [$this->blankItem()],
        ], $overrides);
    }

    private function generateClientReferenceNo(): string
    {
        $existing = collect($this->orders)
            ->map(fn (array $order): string => (string) ($order['client_reference_no'] ?? ''))
            ->filter()
            ->all();

        do {
            $reference = 'BC-'.now()->format('Ymd').'-'.strtoupper(str()->random(6));
        } while (in_array($reference, $existing, true));

        return $reference;
    }

    private function blankItem(array $overrides = []): array
    {
        return array_merge([
            'row_key' => (string) str()->uuid(),
            'product_id' => null,
            'product_name' => '',
            'unit_id' => null,
            'unit_name' => '',
            'amount' => '1',
            'remark' => '',
            'client_line_id' => '',
            'client_item_no' => '',
            'client_product_code' => '',
            'advanced_open' => false,
        ], $overrides);
    }

    private function activeOrderIndex(): ?int
    {
        if ($this->orders === []) {
            $this->activeOrderKey = null;
            $this->activeItemKey = null;

            return null;
        }

        $index = $this->activeOrderKey ? $this->orderIndex($this->activeOrderKey) : null;

        if ($index === null) {
            $this->activeOrderKey = $this->orders[0]['row_key'];
            $this->activeItemKey = $this->orders[0]['items'][0]['row_key'] ?? null;

            return 0;
        }

        return $index;
    }

    private function orderIndex(string $orderKey): ?int
    {
        $index = collect($this->orders)->search(fn (array $order): bool => ($order['row_key'] ?? null) === $orderKey);

        return $index === false ? null : (int) $index;
    }

    private function itemIndex(int $orderIndex, string $itemKey): ?int
    {
        $index = collect($this->orders[$orderIndex]['items'] ?? [])->search(fn (array $item): bool => ($item['row_key'] ?? null) === $itemKey);

        return $index === false ? null : (int) $index;
    }

    /** @return array<int, array<string, mixed>> */
    private function serviceOrders(): array
    {
        return array_values(array_map(fn (array $order): array => [
            'client_reference_no' => $order['client_reference_no'],
            'customer_rec_id' => $order['customer_rec_id'],
            'remark' => $order['remark'],
            'items' => array_values(array_map(fn (array $item): array => [
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'amount' => (string) $item['amount'],
                'remark' => $item['remark'],
                'client_line_id' => $item['client_line_id'],
                'client_item_no' => $item['client_item_no'],
                'client_product_code' => $item['client_product_code'],
            ], $order['items'] ?? [])),
        ], $this->orders));
    }

    /** @param array<string, mixed> $order */
    private function uiOrderFromServiceOrder(array $order): array
    {
        return $this->blankOrder([
            'client_reference_no' => (string) ($order['client_reference_no'] ?? ''),
            'customer_rec_id' => $order['customer_rec_id'] ?? null,
            'remark' => (string) ($order['remark'] ?? ''),
            'items' => array_map(fn (array $item): array => $this->blankItem($item), $order['items'] ?? []),
        ]);
    }

    private function validateForReview(): bool
    {
        $this->resetErrorBag();
        $this->validationSummary = [];

        try {
            Validator::make([
                'batch_reference_no' => $this->batchReferenceNo,
                'batch_date' => $this->batchDate,
                'orders' => $this->serviceOrders(),
            ], [
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
            ], __('bulk_order_checking.validation'))->validate();
        } catch (ValidationException $exception) {
            $this->mapValidationErrors($exception->errors());

            return false;
        }

        return true;
    }

    private function mapValidationErrors(mixed $errors): void
    {
        if (! is_array($errors) || $errors === []) {
            return;
        }

        foreach ($errors as $field => $messages) {
            $mapped = $this->mapErrorField((string) $field);
            $message = $messages[0] ?? __('bulk_order_checking.errors.validation_failed');
            $this->addError($mapped, $message);
            $this->validationSummary[] = [
                'field' => $mapped,
                'source' => (string) $field,
                'message' => $message,
                'order_key' => $this->orderKeyFromField((string) $field),
            ];
        }
    }

    private function mapErrorField(string $field): string
    {
        if ($field === 'batch_reference_no') {
            return 'batchReferenceNo';
        }

        if ($field === 'batch_date') {
            return 'batchDate';
        }

        if (preg_match('/^orders\.(\d+)\.(.+)$/', $field, $matches)) {
            $orderIndex = (int) $matches[1];
            $orderKey = $this->orders[$orderIndex]['row_key'] ?? $orderIndex;
            $tail = $matches[2];

            if (preg_match('/^items\.(\d+)\.(.+)$/', $tail, $itemMatches)) {
                $itemIndex = (int) $itemMatches[1];
                $itemKey = $this->orders[$orderIndex]['items'][$itemIndex]['row_key'] ?? $itemIndex;

                return "orders.{$orderKey}.items.{$itemKey}.{$itemMatches[2]}";
            }

            return "orders.{$orderKey}.{$tail}";
        }

        return $field;
    }

    private function orderKeyFromField(string $field): ?string
    {
        if (preg_match('/^orders\.(\d+)\./', $field, $matches)) {
            return $this->orders[(int) $matches[1]]['row_key'] ?? null;
        }

        return null;
    }

    private function activateFirstValidationOrder(): void
    {
        $orderKey = collect($this->validationSummary)->pluck('order_key')->filter()->first();

        if (is_string($orderKey)) {
            $this->selectOrder($orderKey);
        }
    }

    private function orderStatuses(): array
    {
        $errorKeys = collect($this->validationSummary)->pluck('order_key')->filter()->all();
        $duplicates = collect($this->orders)
            ->map(fn (array $order): string => trim((string) ($order['client_reference_no'] ?? '')))
            ->filter()
            ->duplicates()
            ->values()
            ->all();

        return array_map(function (array $order) use ($errorKeys, $duplicates): array {
            $hasDuplicate = in_array(trim((string) ($order['client_reference_no'] ?? '')), $duplicates, true);
            $hasError = in_array($order['row_key'], $errorKeys, true) || $hasDuplicate;
            $complete = filled($order['client_reference_no'] ?? null)
                && filled($order['customer_rec_id'] ?? null)
                && count($order['items'] ?? []) > 0
                && collect($order['items'] ?? [])->every(fn (array $item): bool => filled($item['product_id'] ?? null)
                    && filled($item['unit_id'] ?? null)
                    && is_numeric($item['amount'] ?? null)
                    && (float) $item['amount'] >= 0.0001);

            $status = $hasError ? 'error' : ($complete ? 'complete' : 'incomplete');

            return [
                'key' => $order['row_key'],
                'status' => $status,
                'label' => __('bulk_order_checking.status.'.$status),
                'icon' => match ($status) {
                    'complete' => '✓',
                    'error' => '!',
                    default => '…',
                },
            ];
        }, $this->orders);
    }

    private function hasMeaningfulData(): bool
    {
        if (filled($this->batchReferenceNo)) {
            return true;
        }

        foreach ($this->orders as $order) {
            if (blank($order['client_reference_no'] ?? null) || ! str_starts_with((string) $order['client_reference_no'], 'BC-')) {
                return true;
            }

            if (filled($order['customer_rec_id'] ?? null) || filled($order['remark'] ?? null)) {
                return true;
            }

            foreach ($order['items'] ?? [] as $item) {
                if (filled($item['product_id'] ?? null) || (string) ($item['amount'] ?? '1') !== '1' || filled($item['remark'] ?? null)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function safeApiMessage(SisahygoApiException $exception): string
    {
        return app(SisahygoApiErrorMessage::class)->message($exception, 'bulk_order_checking');
    }
}
