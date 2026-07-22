@php
    $summary = $this->reviewSummary;
    $activeOrder = $this->activeOrder;
    $activeOrderIndex = max(0, $this->activeOrderPosition - 1);
    $orderStatuses = collect($this->orderStatuses)->keyBy('key');
    $filteredRows = $this->filteredResultRows;
    $resultRows = collect($processedResult['results'] ?? []);
    $successCount = $resultRows->where('status', 'success')->count();
    $failedCount = $resultRows->where('status', 'failed')->count();
    $currentAccount = app()->bound(\App\Domain\ClientAccount\Models\ClientAccount::class) ? app(\App\Domain\ClientAccount\Models\ClientAccount::class) : null;
@endphp

<div
    class="space-y-6"
    x-data="{
        dirty: @entangle('dirty'),
        copied: null,
        copy(text, label) {
            if (!navigator.clipboard) { this.copied = '{{ __('bulk_order_checking.copy.failure') }}'; return; }
            navigator.clipboard.writeText(text).then(() => this.copied = label || '{{ __('bulk_order_checking.copy.success') }}').catch(() => this.copied = '{{ __('bulk_order_checking.copy.failure') }}');
            setTimeout(() => this.copied = null, 2200);
        }
    }"
    x-init="window.addEventListener('beforeunload', function (event) { if (dirty) { event.preventDefault(); event.returnValue = '{{ __('bulk_order_checking.dirty.beforeunload') }}'; } })"
    x-on:keydown.alt.i.window.prevent="$wire.addItem()"
>
    <x-connect.page-header :title="__('bulk_order_checking.title')" :description="__('bulk_order_checking.subtitle')" :eyebrow="__('bulk_order_checking.eyebrow')">
        <x-slot:actions>
            <x-connect.button variant="secondary" href="{{ route('order-checking') }}" wire:navigate>{{ __('bulk_order_checking.actions.single_order') }}</x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('bulk_order_checking.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @else
        <section class="sticky top-16 z-20 rounded-lg border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur" aria-label="{{ __('bulk_order_checking.batch.toolbar') }}">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="batch-reference" class="text-xs font-semibold text-slate-600">{{ __('bulk_order_checking.fields.batch_reference_no') }}</label>
                        <input id="batch-reference" wire:model.blur="batchReferenceNo" maxlength="100" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('batchReferenceNo') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="batch-date" class="text-xs font-semibold text-slate-600">{{ __('bulk_order_checking.fields.batch_date') }}</label>
                        <input id="batch-date" type="date" wire:model.blur="batchDate" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('batchDate') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-sm">
                        <p class="text-xs text-slate-500">{{ __('bulk_order_checking.batch.account') }}</p>
                        <p class="mt-1 truncate font-semibold text-connect-navy-900">{{ $currentAccount?->name ?? '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-sm">
                        <p class="text-xs text-slate-500">{{ __('bulk_order_checking.review.title') }}</p>
                        <p class="mt-1 font-semibold text-connect-navy-900">{{ $summary['orders'] }} {{ __('bulk_order_checking.review.order_count') }} · {{ $summary['items'] }} {{ __('bulk_order_checking.review.item_count') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <x-connect.button variant="secondary" wire:click="beginReview" wire:loading.attr="disabled" wire:target="beginReview">{{ __('bulk_order_checking.actions.review') }}</x-connect.button>
                    @if ($step === 'review')
                        <x-connect.button wire:click="confirmSubmit" wire:loading.attr="disabled" wire:target="confirmSubmit" :disabled="$isSubmitting">
                            <span wire:loading.remove wire:target="confirmSubmit">{{ __('bulk_order_checking.actions.confirm_submit') }}</span>
                            <span wire:loading wire:target="confirmSubmit">{{ __('bulk_order_checking.actions.submitting') }}</span>
                        </x-connect.button>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">{{ __('bulk_order_checking.batch.reference_note') }} {{ __('bulk_order_checking.safety.partial') }} {{ __('bulk_order_checking.transient.form') }}</p>
        </section>

        <div aria-live="polite" class="space-y-3">
            @if ($notice)
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800" role="status">{{ $notice }}</div>
            @endif
            @if ($retryBanner)
                <div class="rounded-lg border border-connect-blue-200 bg-connect-blue-50 p-3 text-sm font-medium text-connect-blue-800" role="status">{{ $retryBanner }}</div>
            @endif
            @if ($pageError)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-900" role="alert">{{ $pageError }}</div>
            @endif
            @if ($unknownMessage)
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert" aria-live="assertive">
                    <p class="font-semibold">{{ __('bulk_order_checking.unknown.title') }}</p>
                    <p class="mt-1">{{ $unknownMessage }}</p>
                </div>
            @endif
            <template x-if="copied"><div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800" x-text="copied"></div></template>
        </div>

        @if ($validationSummary !== [])
            <x-connect.card :title="__('bulk_order_checking.validation_summary.title')">
                <div class="flex flex-wrap gap-2">
                    @foreach ($validationSummary as $issue)
                        <button type="button" wire:click="jumpToValidation('{{ $issue['order_key'] }}')" class="connect-focus rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-left text-sm font-medium text-red-800">
                            {{ $issue['message'] }}
                        </button>
                    @endforeach
                </div>
            </x-connect.card>
        @endif

        @if ($step === 'review')
            <x-connect.card :title="__('bulk_order_checking.review_batch.title')" :description="__('bulk_order_checking.review_batch.description')">
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('bulk_order_checking.fields.batch_reference_no') }}</p><p class="mt-1 break-words font-semibold text-connect-navy-900">{{ $batchReferenceNo ?: '—' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('bulk_order_checking.fields.batch_date') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ $batchDate ?: '—' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('bulk_order_checking.review.order_count') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ $summary['orders'] }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('bulk_order_checking.review.item_count') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ $summary['items'] }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('bulk_order_checking.review.complete_count') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ $summary['complete'] }}/{{ $summary['orders'] }}</p></div>
                </div>
                <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                    @foreach ($orders as $index => $order)
                        @php $status = $orderStatuses->get($order['row_key']); @endphp
                        <button type="button" wire:click="backToEdit('{{ $order['row_key'] }}')" class="connect-focus grid w-full gap-2 border-b border-slate-100 p-4 text-left text-sm last:border-b-0 md:grid-cols-[4rem_1fr_1fr_7rem_8rem] md:items-center">
                            <span class="font-semibold text-slate-500">#{{ $index + 1 }}</span>
                            <span class="break-words font-semibold text-connect-navy-900">{{ $order['client_reference_no'] ?: __('bulk_order_checking.orders.missing_reference') }}</span>
                            <span class="break-words text-slate-600">{{ data_get($order, 'selected_receiver.name') ?? __('bulk_order_checking.receiver.empty_selected') }}</span>
                            <span>{{ count($order['items'] ?? []) }} {{ __('bulk_order_checking.items.title') }}</span>
                            <span class="font-semibold">{{ $status['icon'] ?? '…' }} {{ $status['label'] ?? __('bulk_order_checking.status.incomplete') }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{ __('bulk_order_checking.review_batch.warning') }}</div>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <x-connect.button variant="secondary" wire:click="backToEdit">{{ __('bulk_order_checking.actions.back_to_edit') }}</x-connect.button>
                    <x-connect.button wire:click="confirmSubmit" wire:loading.attr="disabled" wire:target="confirmSubmit" :disabled="$isSubmitting">
                        <span wire:loading.remove wire:target="confirmSubmit">{{ __('bulk_order_checking.actions.confirm_submit') }}</span>
                        <span wire:loading wire:target="confirmSubmit">{{ __('bulk_order_checking.actions.submitting') }}</span>
                    </x-connect.button>
                </div>
            </x-connect.card>
        @elseif ($step === 'result' && $processedResult)
            <x-connect.card :title="__('bulk_order_checking.results.title')">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">{{ __('bulk_order_checking.transient.result') }}</div>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs text-slate-500">{{ __('bulk_order_checking.results.api_batch_no') }}</dt><dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ $processedResult['api_batch_no'] ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs text-slate-500">{{ __('bulk_order_checking.fields.batch_reference_no') }}</dt><dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ $processedResult['batch_reference_no'] ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs text-slate-500">{{ __('bulk_order_checking.fields.batch_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $processedResult['batch_date'] ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs text-slate-500">{{ __('bulk_order_checking.results.total') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $processedResult['summary']['total'] ?? 0 }}</dd></div>
                    <div class="rounded-lg bg-emerald-50 p-4"><dt class="text-xs text-emerald-700">{{ __('bulk_order_checking.results.succeeded') }}</dt><dd class="mt-1 font-semibold text-emerald-800">{{ $successCount }}</dd></div>
                    <div class="rounded-lg bg-red-50 p-4"><dt class="text-xs text-red-700">{{ __('bulk_order_checking.results.failed_count') }}</dt><dd class="mt-1 font-semibold text-red-800">{{ $failedCount }}</dd></div>
                </dl>

                <div class="mt-5 flex flex-wrap gap-2" role="tablist" aria-label="{{ __('bulk_order_checking.results.filters') }}">
                    @foreach ([['all', __('bulk_order_checking.results.filter_all').' ('.$resultRows->count().')'], ['success', __('bulk_order_checking.results.filter_success').' ('.$successCount.')'], ['failed', __('bulk_order_checking.results.filter_failed').' ('.$failedCount.')']] as [$filter, $label])
                        <button type="button" wire:click="$set('resultFilter', '{{ $filter }}')" @class(['connect-focus rounded-lg px-3 py-2 text-sm font-semibold', 'bg-connect-blue-600 text-white' => $resultFilter === $filter, 'border border-slate-200 bg-white text-slate-700' => $resultFilter !== $filter]) aria-selected="{{ $resultFilter === $filter ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                    <button type="button" class="connect-focus rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700" x-on:click="copy(@js($this->visibleResultTsv), '{{ __('bulk_order_checking.copy.success') }}')">{{ __('bulk_order_checking.actions.copy_visible') }}</button>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                    @foreach ($filteredRows as $row)
                        <div class="grid gap-2 border-b border-slate-100 p-4 text-sm last:border-b-0 lg:grid-cols-[1fr_9rem_8rem_1fr_auto] lg:items-center">
                            <div class="min-w-0"><p class="break-words font-semibold text-connect-navy-900">{{ $row['client_reference_no'] ?? '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $row['message'] ?? ($row['status'] === 'failed' ? __('bulk_order_checking.results.failed_generic') : __('bulk_order_checking.results.accepted')) }}</p></div>
                            <p><span class="text-slate-500">{{ __('bulk_order_checking.results.tracking_no') }}:</span> <span class="font-semibold">{{ $row['tracking_no'] ?? '—' }}</span></p>
                            <p><span class="text-slate-500">{{ __('bulk_order_checking.results.order_status') }}:</span> <span class="font-semibold">{{ $row['order_status'] ?? $row['status'] }}</span></p>
                            <p class="text-xs text-slate-500">{{ $row['error_code'] ?? '' }} @if(! empty($row['details'])) {{ collect($row['details'])->flatten()->first() }} @endif</p>
                            <div class="flex gap-2 lg:justify-end">
                                <button type="button" class="connect-focus rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700" x-on:click="copy(@js($row['client_reference_no'] ?? ''), '{{ __('bulk_order_checking.copy.success') }}')">{{ __('bulk_order_checking.actions.copy_reference') }}</button>
                                @if ($row['tracking_no'] ?? null)
                                    <button type="button" class="connect-focus rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700" x-on:click="copy(@js($row['tracking_no']), '{{ __('bulk_order_checking.copy.success') }}')">{{ __('bulk_order_checking.actions.copy_tracking') }}</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    @if ($failedCount > 0)
                        <x-connect.button variant="warning" wire:click="prepareFailedRetry">{{ __('bulk_order_checking.actions.retry_failed') }}</x-connect.button>
                    @endif
                    <x-connect.button variant="secondary" href="{{ route('tracking') }}" wire:navigate>{{ __('bulk_order_checking.actions.tracking') }}</x-connect.button>
                    <x-connect.button variant="secondary" href="{{ route('history') }}" wire:navigate>{{ __('bulk_order_checking.actions.history') }}</x-connect.button>
                    <x-connect.button wire:click="createAnother">{{ __('bulk_order_checking.actions.create_another') }}</x-connect.button>
                </div>
            </x-connect.card>
        @else
            <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
                <aside class="space-y-4 xl:sticky xl:top-40 xl:self-start">
                    <x-connect.card :title="__('bulk_order_checking.navigator.title')" padding="none">
                        <div class="border-b border-slate-100 p-4">
                            <div class="flex flex-wrap gap-2">
                                <x-connect.button size="sm" wire:click="addOrder" :disabled="count($orders) >= 50">{{ __('bulk_order_checking.actions.add_order') }}</x-connect.button>
                                <x-connect.button size="sm" variant="secondary" wire:click="duplicateActiveOrder">{{ __('bulk_order_checking.actions.duplicate_order') }}</x-connect.button>
                                <x-connect.button size="sm" variant="ghost" wire:click="removeActiveOrder" onclick="return confirm('{{ __('bulk_order_checking.actions.remove_active_order_confirm') }}')" :disabled="count($orders) <= 1">{{ __('bulk_order_checking.actions.remove_active_order') }}</x-connect.button>
                            </div>
                        </div>
                        <nav class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto" aria-label="{{ __('bulk_order_checking.navigator.title') }}">
                            @foreach ($orders as $index => $order)
                                @php $status = $orderStatuses->get($order['row_key']); @endphp
                                <button type="button" wire:key="navigator-{{ $order['row_key'] }}" wire:click="selectOrder('{{ $order['row_key'] }}')" @class(['connect-focus block w-full p-4 text-left transition hover:bg-slate-50', 'bg-connect-blue-50' => $activeOrderKey === $order['row_key']]) aria-current="{{ $activeOrderKey === $order['row_key'] ? 'step' : 'false' }}">
                                    <div class="flex items-center justify-between gap-3"><span class="text-xs font-semibold text-slate-500">{{ __('bulk_order_checking.orders.short_title', ['number' => $index + 1]) }}</span><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $status['icon'] ?? '…' }} {{ $status['label'] ?? __('bulk_order_checking.status.incomplete') }}</span></div>
                                    <p class="mt-2 break-words text-sm font-semibold text-connect-navy-900">{{ $order['client_reference_no'] ?: __('bulk_order_checking.orders.missing_reference') }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ data_get($order, 'selected_receiver.name') ?? __('bulk_order_checking.receiver.empty_selected') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ count($order['items'] ?? []) }} {{ __('bulk_order_checking.items.title') }}</p>
                                </button>
                            @endforeach
                        </nav>
                    </x-connect.card>
                </aside>

                <main class="space-y-4">
                    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="text-xs font-semibold text-connect-blue-700">{{ __('bulk_order_checking.active_order.title') }}</p><h2 class="mt-1 text-lg font-semibold text-connect-navy-900">{{ __('bulk_order_checking.orders.card_title', ['number' => $this->activeOrderPosition]) }} {{ __('bulk_order_checking.mobile.of_total', ['total' => count($orders)]) }}</h2></div>
                        <div class="flex flex-wrap gap-2"><x-connect.button size="sm" variant="secondary" wire:click="previousOrder" :disabled="$this->activeOrderPosition <= 1">{{ __('bulk_order_checking.actions.previous_order') }}</x-connect.button><x-connect.button size="sm" variant="secondary" wire:click="nextOrder" :disabled="$this->activeOrderPosition >= count($orders)">{{ __('bulk_order_checking.actions.next_order') }}</x-connect.button></div>
                    </div>

                    @if ($activeOrder)
                        <div wire:key="active-order-editor-{{ $activeOrder['row_key'] }}" class="space-y-4">
                        <x-connect.card :title="__('bulk_order_checking.active_order.details')">
                            <fieldset class="space-y-4">
                                <legend class="sr-only">{{ __('bulk_order_checking.active_order.details') }}</legend>
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <label for="active-reference" class="text-sm font-semibold text-slate-700">{{ __('bulk_order_checking.fields.client_reference_no') }}</label>
                                        <input id="active-reference" wire:model.blur="orders.{{ $activeOrderIndex }}.client_reference_no" maxlength="100" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                        @error("orders.{$activeOrder['row_key']}.client_reference_no") <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">{{ __('bulk_order_checking.receiver.selected_label') }}</p>
                                        <div class="mt-1 rounded-lg border border-connect-blue-100 bg-connect-blue-50 p-3 text-sm"><p class="break-words font-semibold text-connect-navy-900">{{ data_get($activeOrder, 'selected_receiver.name') ?? __('bulk_order_checking.receiver.empty_selected') }}</p>@if(data_get($activeOrder, 'selected_receiver.phone'))<p class="text-xs text-slate-500">{{ data_get($activeOrder, 'selected_receiver.phone') }}</p>@endif</div>
                                        @error("orders.{$activeOrder['row_key']}.customer_rec_id") <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label for="active-remark" class="text-sm font-semibold text-slate-700">{{ __('bulk_order_checking.fields.remark') }}</label>
                                        <textarea id="active-remark" wire:model.blur="orders.{{ $activeOrderIndex }}.remark" rows="2" maxlength="150" class="connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm"></textarea>
                                    </div>
                                </div>
                            </fieldset>
                        </x-connect.card>

                        <x-connect.card :title="__('bulk_order_checking.lookup.receiver_title')">
                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                <div><label for="receiver-search" class="text-sm font-semibold text-slate-700">{{ __('bulk_order_checking.receiver.search_label') }}</label><input id="receiver-search" type="search" wire:model.live.debounce.400ms="receiverSearch" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('bulk_order_checking.receiver.search_placeholder') }}"></div>
                                <x-connect.button variant="ghost" wire:click="clearReceiver">{{ __('bulk_order_checking.actions.clear_receiver') }}</x-connect.button>
                            </div>
                            <div wire:loading wire:target="receiverSearch" class="mt-3 text-sm text-slate-500">{{ __('bulk_order_checking.lookup.loading') }}</div>
                            @if ($receiverSearch !== '')
                                <div class="mt-3 overflow-hidden rounded-lg border border-slate-200" role="listbox">
                                    @forelse ($receiverResults as $receiver)
                                        <button type="button" wire:key="receiver-{{ $receiver['customer_id'] }}" wire:click="selectReceiver(null, {{ $receiver['customer_id'] }})" class="connect-focus block w-full border-b border-slate-100 p-3 text-left text-sm last:border-b-0 hover:bg-connect-blue-50" role="option"><span class="font-semibold text-connect-navy-900">{{ $receiver['name'] }}</span><span class="ml-2 text-xs text-slate-500">{{ $receiver['phone'] ?: __('bulk_order_checking.receiver.no_phone') }}</span></button>
                                    @empty
                                        <p class="p-3 text-sm text-slate-500">{{ mb_strlen(trim($receiverSearch)) < 2 ? __('bulk_order_checking.receiver.min_search') : __('bulk_order_checking.receiver.no_results') }}</p>
                                    @endforelse
                                </div>
                            @endif
                        </x-connect.card>

                        <x-connect.card :title="__('bulk_order_checking.items.title')" :description="__('bulk_order_checking.items.shortcut_hint')">
                            <div class="mb-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                <div><label for="product-search" class="text-sm font-semibold text-slate-700">{{ __('bulk_order_checking.products.search_label') }}</label><input id="product-search" type="search" wire:model.live.debounce.400ms="productSearch" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('bulk_order_checking.products.search_placeholder') }}"></div>
                                <x-connect.button variant="secondary" wire:click="addItem">{{ __('bulk_order_checking.actions.add_item') }}</x-connect.button>
                            </div>
                            <div wire:loading wire:target="productSearch" class="mb-3 text-sm text-slate-500">{{ __('bulk_order_checking.lookup.loading') }}</div>
                            <div class="space-y-3">
                                @foreach ($activeOrder['items'] as $itemIndex => $item)
                                    @php $itemKey = $item['row_key']; @endphp
                                    <div wire:key="active-item-{{ $itemKey }}" class="rounded-lg border border-slate-200 p-4" x-data="{ advanced: false }">
                                        <div class="grid gap-3 xl:grid-cols-[3rem_minmax(0,1fr)_9rem_8rem_8rem] xl:items-start">
                                            <p class="pt-8 text-sm font-semibold text-slate-500">#{{ $itemIndex + 1 }}</p>
                                            <div>
                                                <label for="item-{{ $itemKey }}-product" class="text-xs font-semibold text-slate-500">{{ __('bulk_order_checking.fields.product') }}</label>
                                                <button id="item-{{ $itemKey }}-product" type="button" wire:click="setActiveItem('{{ $itemKey }}')" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-left text-sm shadow-sm">{{ $item['product_name'] ?: __('bulk_order_checking.items.product_empty') }}</button>
                                                @if ($activeItemKey === $itemKey && $productSearch !== '')
                                                    <div class="mt-2 overflow-hidden rounded-lg border border-slate-200" role="listbox">
                                                        @forelse ($productResults as $product)
                                                            <button type="button" wire:key="product-{{ $product['product_id'] }}-{{ $product['unit_id'] }}" wire:click="selectProduct(null, '{{ $itemKey }}', {{ $product['product_id'] }}, {{ $product['unit_id'] }})" class="connect-focus block w-full border-b border-slate-100 p-3 text-left text-xs last:border-b-0 hover:bg-connect-blue-50" role="option">{{ $product['product_name'] }} / {{ $product['unit_name'] }}</button>
                                                        @empty
                                                            <p class="p-3 text-xs text-slate-500">{{ mb_strlen(trim($productSearch)) < 2 ? __('bulk_order_checking.products.min_search') : __('bulk_order_checking.products.no_results') }}</p>
                                                        @endforelse
                                                    </div>
                                                @endif
                                                @error("orders.{$activeOrder['row_key']}.items.{$itemKey}.product_id") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            <div><label for="item-{{ $itemKey }}-unit" class="text-xs font-semibold text-slate-500">{{ __('bulk_order_checking.fields.unit') }}</label><select id="item-{{ $itemKey }}-unit" wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.unit_id" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="">{{ __('bulk_order_checking.items.unit_placeholder') }}</option>@foreach ($units as $unit)<option value="{{ data_get($unit, 'unit_id') }}">{{ data_get($unit, 'unit_name', '-') }}</option>@endforeach</select>@error("orders.{$activeOrder['row_key']}.items.{$itemKey}.unit_id") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror</div>
                                            <div><label for="item-{{ $itemKey }}-amount" class="text-xs font-semibold text-slate-500">{{ __('bulk_order_checking.fields.amount') }}</label><input id="item-{{ $itemKey }}-amount" type="number" min="0.0001" step="0.0001" wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.amount" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">@error("orders.{$activeOrder['row_key']}.items.{$itemKey}.amount") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror</div>
                                            <div class="flex gap-2 pt-6"><button type="button" class="connect-focus rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700" x-on:click="advanced = ! advanced">{{ __('bulk_order_checking.actions.advanced') }}</button><button type="button" wire:click="removeItem('{{ $activeOrder['row_key'] }}', '{{ $itemKey }}')" class="connect-focus rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700">{{ __('bulk_order_checking.actions.remove_item_context', ['number' => $itemIndex + 1]) }}</button></div>
                                        </div>
                                        <div x-show="advanced" x-cloak class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                            <input wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.remark" maxlength="200" class="connect-focus min-h-11 rounded-lg border-slate-300 text-sm shadow-sm xl:col-span-2" aria-label="{{ __('bulk_order_checking.fields.item_remark') }}" placeholder="{{ __('bulk_order_checking.fields.item_remark') }}">
                                            <input wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.client_line_id" maxlength="100" class="connect-focus min-h-11 rounded-lg border-slate-300 text-sm shadow-sm" aria-label="{{ __('bulk_order_checking.fields.client_line_id') }}" placeholder="{{ __('bulk_order_checking.fields.client_line_id') }}">
                                            <input wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.client_item_no" maxlength="100" class="connect-focus min-h-11 rounded-lg border-slate-300 text-sm shadow-sm" aria-label="{{ __('bulk_order_checking.fields.client_item_no') }}" placeholder="{{ __('bulk_order_checking.fields.client_item_no') }}">
                                            <input wire:model.blur="orders.{{ $activeOrderIndex }}.items.{{ $itemIndex }}.client_product_code" maxlength="100" class="connect-focus min-h-11 rounded-lg border-slate-300 text-sm shadow-sm" aria-label="{{ __('bulk_order_checking.fields.client_product_code') }}" placeholder="{{ __('bulk_order_checking.fields.client_product_code') }}">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-connect.card>
                        </div>
                    @endif
                </main>
            </div>
        @endif
    @endif
</div>
