@php
    $readyForReview = $this->readyForReview;
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-connect-blue-700">{{ __('order_checking.eyebrow') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-connect-navy-900 sm:text-3xl">{{ __('order_checking.title') }}</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ __('order_checking.description') }}</p>
        </div>

        <div class="rounded-lg border border-connect-blue-100 bg-connect-blue-50 px-4 py-3 text-sm text-connect-navy-900">
            <p class="font-semibold">{{ __('order_checking.environment.title') }}</p>
            <p class="mt-1">{{ __('order_checking.environment.body') }}</p>
        </div>
    </div>

    @if ($unavailable)
        <x-connect.card :title="__('order_checking.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                {{ $unavailableMessage }}
            </div>
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">{{ $pageError }}</div>
        @endif

        @if ($unknownMessage)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <p class="font-semibold">{{ __('order_checking.unknown.title') }}</p>
                <p class="mt-1">{{ $unknownMessage }}</p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <x-connect.button variant="warning" wire:click="reconcile" wire:loading.attr="disabled" wire:target="reconcile">
                        <span wire:loading.remove wire:target="reconcile">{{ __('order_checking.unknown.reconcile') }}</span>
                        <span wire:loading wire:target="reconcile">{{ __('order_checking.unknown.reconciling') }}</span>
                    </x-connect.button>
                </div>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-4">
                <x-connect.card :title="__('order_checking.receiver.title')" :description="__('order_checking.receiver.description')">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                        <div>
                            @if (count($senderOptions) > 1)
                                <label for="sender" class="text-sm font-semibold text-slate-700">{{ __('order_checking.sender.label') }}</label>
                                <select id="sender" wire:model.blur="selectedSenderCustomerId" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    <option value="">{{ __('order_checking.sender.placeholder') }}</option>
                                    @foreach ($senderOptions as $sender)
                                        <option value="{{ $sender['customer_id'] }}">{{ $sender['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('selectedSenderCustomerId') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                            @else
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                    <p class="text-slate-500">{{ __('order_checking.sender.label') }}</p>
                                    <p class="mt-1 font-semibold text-connect-navy-900">{{ $senderOptions[0]['label'] ?? '-' }}</p>
                                </div>
                            @endif

                            <div class="mt-4">
                                <label for="receiver-search" class="text-sm font-semibold text-slate-700">{{ __('order_checking.receiver.search_label') }}</label>
                                <div class="relative mt-1">
                                    <input id="receiver-search" type="search" wire:model.live.debounce.400ms="receiverSearch" class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 pr-10 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('order_checking.receiver.search_placeholder') }}" autocomplete="off" aria-describedby="receiver-search-help">
                                    <div wire:loading wire:target="receiverSearch" class="absolute right-3 top-3 h-5 w-5 animate-spin rounded-full border-2 border-connect-blue-600 border-t-transparent" aria-label="{{ __('order_checking.loading') }}"></div>
                                </div>
                                <p id="receiver-search-help" class="mt-1 text-xs text-slate-500">{{ __('order_checking.receiver.search_hint') }}</p>
                                @error('selectedReceiver.customer_id') <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p> @enderror

                                <div class="mt-3 overflow-hidden rounded-lg border border-slate-200">
                                    @if (mb_strlen(trim($receiverSearch)) < 2)
                                        <div class="bg-slate-50 p-4 text-sm text-slate-500">{{ __('order_checking.receiver.min_search') }}</div>
                                    @elseif ($receiverResults === [])
                                        <div class="bg-slate-50 p-4 text-sm text-slate-500">{{ __('order_checking.receiver.no_results') }}</div>
                                    @else
                                        <div class="divide-y divide-slate-100 bg-white">
                                            @foreach ($receiverResults as $receiver)
                                                <button type="button" wire:key="receiver-{{ $receiver['customer_id'] }}" wire:click="selectReceiver({{ $receiver['customer_id'] }})" class="connect-focus flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition hover:bg-connect-blue-50">
                                                    <span class="min-w-0">
                                                        <span class="block truncate text-sm font-semibold text-connect-navy-900">{{ $receiver['name'] }}</span>
                                                        <span class="mt-1 block break-words text-xs text-slate-500">{{ $receiver['phone'] ?: __('order_checking.receiver.no_phone') }}</span>
                                                    </span>
                                                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">#{{ $receiver['customer_id'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-connect-blue-100 bg-connect-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-connect-blue-700">{{ __('order_checking.receiver.selected_label') }}</p>
                            @if ($selectedReceiver)
                                <p class="mt-3 break-words font-semibold text-connect-navy-900">{{ $selectedReceiver['name'] }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $selectedReceiver['phone'] ?: __('order_checking.receiver.no_phone') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('order_checking.receiver.branch_derived') }}</p>
                                <button type="button" wire:click="$set('selectedReceiver', null)" class="connect-focus mt-4 rounded-lg text-sm font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ __('order_checking.receiver.change') }}</button>
                            @else
                                <p class="mt-3 text-sm text-slate-600">{{ __('order_checking.receiver.empty_selected') }}</p>
                            @endif
                        </div>
                    </div>
                </x-connect.card>

                <x-connect.card :title="__('order_checking.items.title')" :description="__('order_checking.items.description')">
                    <div>
                        <label for="product-search" class="text-sm font-semibold text-slate-700">{{ __('order_checking.products.search_label') }}</label>
                        <div class="relative mt-1">
                            <input id="product-search" type="search" wire:model.live.debounce.400ms="productSearch" class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 pr-10 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('order_checking.products.search_placeholder') }}" autocomplete="off">
                            <div wire:loading wire:target="productSearch" class="absolute right-3 top-3 h-5 w-5 animate-spin rounded-full border-2 border-connect-blue-600 border-t-transparent" aria-label="{{ __('order_checking.loading') }}"></div>
                        </div>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @forelse ($productResults as $product)
                                <button type="button" wire:key="product-{{ $product['product_id'] }}-{{ $product['unit_id'] }}" wire:click="addProduct({{ $product['product_id'] }}, {{ $product['unit_id'] }})" class="connect-focus rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-connect-blue-300 hover:bg-connect-blue-50">
                                    <span class="block break-words text-sm font-semibold text-connect-navy-900">{{ $product['product_name'] }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">#{{ $product['product_id'] }} · {{ $product['unit_name'] }}</span>
                                </button>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 sm:col-span-2 xl:col-span-3">{{ mb_strlen(trim($productSearch)) < 2 ? __('order_checking.products.min_search') : __('order_checking.products.no_results') }}</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($items as $index => $item)
                            @php($rowKey = $item['row_key'])
                            <div wire:key="item-row-{{ $rowKey }}" class="grid gap-3 rounded-lg border border-slate-200 p-4 lg:grid-cols-[minmax(0,1fr)_9rem_7rem_auto] lg:items-start">
                                <div class="min-w-0">
                                    <label for="item-{{ $rowKey }}-product" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.product') }}</label>
                                    <input id="item-{{ $rowKey }}-product" value="{{ $item['product_name'] ?: __('order_checking.items.product_empty') }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300 bg-slate-50 text-sm shadow-sm" disabled>
                                    @error("items.{$rowKey}.product_id") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="item-{{ $rowKey }}-unit" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.unit') }}</label>
                                    <select id="item-{{ $rowKey }}-unit" wire:model.blur="items.{{ $index }}.unit_id" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                        <option value="">{{ __('order_checking.items.unit_placeholder') }}</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit['unit_id'] }}">{{ $unit['unit_name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error("items.{$rowKey}.unit_id") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="item-{{ $rowKey }}-amount" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.amount') }}</label>
                                    <input id="item-{{ $rowKey }}-amount" type="number" min="0.0001" step="0.0001" wire:model.blur="items.{{ $index }}.amount" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    @error("items.{$rowKey}.amount") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" wire:click="removeItem('{{ $rowKey }}')" class="connect-focus mt-5 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">{{ __('order_checking.items.remove') }}</button>
                                <div class="lg:col-span-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="xl:col-span-2">
                                        <label for="item-{{ $rowKey }}-remark" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.remark') }}</label>
                                        <input id="item-{{ $rowKey }}-remark" wire:model.blur="items.{{ $index }}.remark" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.remark_placeholder') }}">
                                        @error("items.{$rowKey}.remark") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="item-{{ $rowKey }}-line" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.client_line_id') }}</label>
                                        <input id="item-{{ $rowKey }}-line" wire:model.blur="items.{{ $index }}.client_line_id" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    </div>
                                    <div>
                                        <label for="item-{{ $rowKey }}-code" class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.client_product_code') }}</label>
                                        <input id="item-{{ $rowKey }}-code" wire:model.blur="items.{{ $index }}.client_product_code" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @error('items') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </x-connect.card>

                <x-connect.card :title="__('order_checking.reference.title')" :description="__('order_checking.reference.description')">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="client-reference" class="text-sm font-semibold text-slate-700">{{ __('order_checking.reference.reference_label') }}</label>
                            <input id="client-reference" wire:model.blur="clientReferenceNo" maxlength="100" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            @error('clientReferenceNo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="order-remark" class="text-sm font-semibold text-slate-700">{{ __('order_checking.reference.remark_label') }}</label>
                            <textarea id="order-remark" wire:model.blur="orderRemark" rows="3" maxlength="150" class="connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.reference.remark_placeholder') }}"></textarea>
                            @error('orderRemark') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </x-connect.card>

                <x-connect.card :title="__('order_checking.review.title')" :description="__('order_checking.review.description')">
                    <div class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-slate-500">{{ __('order_checking.review.receiver') }}</p><p class="mt-1 break-words font-semibold text-connect-navy-900">{{ $selectedReceiver['name'] ?? __('order_checking.review.missing') }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-slate-500">{{ __('order_checking.review.reference') }}</p><p class="mt-1 break-words font-semibold text-connect-navy-900">{{ $clientReferenceNo ?: __('order_checking.review.missing') }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-slate-500">{{ __('order_checking.review.items') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ trans_choice('order_checking.review.item_count', count($items), ['count' => count($items)]) }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-4"><p class="text-slate-500">{{ __('order_checking.review.status') }}</p><p class="mt-1 font-semibold {{ $readyForReview ? 'text-emerald-700' : 'text-amber-700' }}">{{ $readyForReview ? __('order_checking.review.ready') : __('order_checking.review.not_ready') }}</p></div>
                    </div>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <x-connect.button wire:click="submit" wire:loading.attr="disabled" wire:target="submit" :disabled="! $readyForReview || $isSubmitting">
                            <span wire:loading.remove wire:target="submit">{{ __('order_checking.review.submit') }}</span>
                            <span wire:loading wire:target="submit">{{ __('order_checking.review.submitting') }}</span>
                        </x-connect.button>
                    </div>
                </x-connect.card>
            </div>

            <aside class="space-y-4">
                <x-connect.card :title="__('order_checking.progress.title')">
                    <div class="space-y-3">
                        @foreach ([
                            ['done' => (bool) $selectedReceiver, 'label' => __('order_checking.progress.receiver')],
                            ['done' => collect($items)->contains(fn ($item) => filled($item['product_id'])), 'label' => __('order_checking.progress.items')],
                            ['done' => trim($clientReferenceNo) !== '', 'label' => __('order_checking.progress.reference')],
                            ['done' => $readyForReview, 'label' => __('order_checking.progress.review')],
                        ] as $step)
                            <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $step['done'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $step['done'] ? '✓' : '·' }}</span>
                                <span class="text-sm font-medium text-slate-700">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-connect.card>

                <x-connect.card :title="__('order_checking.help.title')">
                    <div class="space-y-3 text-sm leading-6 text-slate-600">
                        <p>{{ __('order_checking.help.body') }}</p>
                        <p class="rounded-lg bg-slate-50 p-3">{{ __('order_checking.help.validation_body') }}</p>
                    </div>
                </x-connect.card>
            </aside>
        </div>
    @endif

    @if ($state === 'success' && $successResult)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="success-title">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl" tabindex="-1">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl font-bold text-emerald-700">✓</div>
                <h3 id="success-title" class="mt-4 text-xl font-semibold text-connect-navy-900">{{ __('order_checking.success.title') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('order_checking.success.body') }}</p>
                <div class="mt-4 space-y-2 rounded-lg bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-slate-500">{{ __('order_checking.review.reference') }}</span><span class="break-words text-right font-semibold text-connect-navy-900">{{ $successResult['client_reference_no'] }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">{{ __('order_checking.success.initial_status') }}</span><span class="font-semibold text-amber-700">{{ $successResult['status_label'] }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">{{ __('order_checking.success.core_reference') }}</span><span class="font-semibold text-connect-navy-900">{{ $successResult['tracking_no'] ?? $successResult['id'] ?? '-' }}</span></div>
                    <div class="flex justify-between gap-4"><span class="text-slate-500">{{ __('order_checking.review.receiver') }}</span><span class="break-words text-right font-semibold text-connect-navy-900">{{ $successResult['receiver_name'] ?? '-' }}</span></div>
                </div>
                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <x-connect.button variant="secondary" href="{{ route('dashboard') }}" wire:navigate>{{ __('order_checking.success.next_action') }}</x-connect.button>
                    <x-connect.button wire:click="createAnother">{{ __('order_checking.success.create_another') }}</x-connect.button>
                </div>
            </div>
        </div>
    @endif
</div>
