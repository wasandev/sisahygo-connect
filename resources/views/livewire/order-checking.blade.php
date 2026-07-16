@php
    $selectedReceiver = $this->selectedReceiver;
    $readyForReview = $this->readyForReview;
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-connect-blue-700">{{ __('order_checking.eyebrow') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-connect-navy-900 sm:text-3xl">{{ __('order_checking.title') }}</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ __('order_checking.description') }}</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">{{ __('order_checking.mock_notice_title') }}</p>
            <p class="mt-1">{{ __('order_checking.mock_notice_body') }}</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-4">
            <x-connect.card :title="__('order_checking.receiver.title')" :description="__('order_checking.receiver.description')">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div>
                        <label for="receiver-search" class="text-sm font-semibold text-slate-700">{{ __('order_checking.receiver.search_label') }}</label>
                        <div class="relative mt-1">
                            <input
                                id="receiver-search"
                                type="search"
                                wire:model.live.debounce.400ms="receiverSearch"
                                class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 pr-10 text-sm shadow-sm placeholder:text-slate-400"
                                placeholder="{{ __('order_checking.receiver.search_placeholder') }}"
                                autocomplete="off"
                            >
                            <div wire:loading wire:target="receiverSearch" class="absolute right-3 top-3 h-5 w-5 animate-spin rounded-full border-2 border-connect-blue-600 border-t-transparent"></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ __('order_checking.receiver.search_hint') }}</p>
                        @error('selectedReceiverId')
                            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200">
                            @if (mb_strlen(trim($receiverSearch)) < 2)
                                <div class="bg-slate-50 p-4 text-sm text-slate-500">{{ __('order_checking.receiver.min_search') }}</div>
                            @elseif (count($this->filteredReceivers) === 0)
                                <div class="bg-slate-50 p-4 text-sm text-slate-500">{{ __('order_checking.receiver.no_results') }}</div>
                            @else
                                <div class="divide-y divide-slate-100 bg-white">
                                    @foreach ($this->filteredReceivers as $receiver)
                                        <button
                                            type="button"
                                            wire:click="selectReceiver('{{ $receiver['id'] }}')"
                                            class="connect-focus flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition hover:bg-connect-blue-50"
                                        >
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-connect-navy-900">{{ $receiver['name'] }}</span>
                                                <span class="mt-1 block text-xs text-slate-500">{{ $receiver['branch'] }} · {{ $receiver['phone'] }}</span>
                                            </span>
                                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $receiver['tag'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-lg border border-connect-blue-100 bg-connect-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-connect-blue-700">{{ __('order_checking.receiver.selected_label') }}</p>
                        @if ($selectedReceiver)
                            <p class="mt-3 font-semibold text-connect-navy-900">{{ $selectedReceiver->name }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $selectedReceiver->branch }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $selectedReceiver->phone }}</p>
                            <button type="button" wire:click="$set('selectedReceiverId', null)" class="connect-focus mt-4 rounded-lg text-sm font-semibold text-connect-blue-700 hover:text-connect-blue-900">
                                {{ __('order_checking.receiver.change') }}
                            </button>
                        @else
                            <p class="mt-3 text-sm text-slate-600">{{ __('order_checking.receiver.empty_selected') }}</p>
                        @endif
                    </div>
                </div>
            </x-connect.card>

            <x-connect.card :title="__('order_checking.items.title')" :description="__('order_checking.items.description')">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($mockProducts as $product)
                        <button type="button" wire:click="addProduct('{{ $product['name'] }}')" class="connect-focus rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-connect-blue-300 hover:bg-connect-blue-50">
                            <span class="block text-sm font-semibold text-connect-navy-900">{{ $product['name'] }}</span>
                            <span class="mt-1 block text-xs text-slate-500">{{ $product['meta'] }} · {{ $product['unit'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($items as $index => $item)
                        <div class="grid gap-3 rounded-lg border border-slate-200 p-4 sm:grid-cols-[minmax(0,1fr)_7rem_6rem_auto] sm:items-start">
                            <div class="min-w-0">
                                <label class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.product') }}</label>
                                <input wire:model.blur="items.{{ $index }}.product" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" />
                                @error("items.{$index}.product") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.unit') }}</label>
                                <input wire:model.blur="items.{{ $index }}.unit" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" />
                                @error("items.{$index}.unit") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.quantity') }}</label>
                                <input type="number" min="0.01" step="0.01" wire:model.blur="items.{{ $index }}.quantity" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" />
                                @error("items.{$index}.quantity") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="button" wire:click="removeItem({{ $index }})" class="connect-focus mt-5 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                                {{ __('order_checking.items.remove') }}
                            </button>
                            <div class="sm:col-span-4">
                                <label class="text-xs font-semibold text-slate-500">{{ __('order_checking.items.remark') }}</label>
                                <input wire:model.blur="items.{{ $index }}.remark" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.remark_placeholder') }}" />
                                @error("items.{$index}.remark") <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">{{ __('order_checking.items.empty') }}</div>
                    @endforelse
                    @error('items') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-connect-navy-900">{{ __('order_checking.items.custom_title') }}</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-[minmax(0,1fr)_8rem_7rem]">
                        <div>
                            <input wire:model.blur="newItem.product" class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.product_placeholder') }}">
                            @error('newItem.product') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input wire:model.blur="newItem.unit" class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.unit') }}">
                            @error('newItem.unit') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input type="number" min="0.01" step="0.01" wire:model.blur="newItem.quantity" class="connect-focus block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.quantity') }}">
                            @error('newItem.quantity') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input wire:model.blur="newItem.remark" class="connect-focus min-h-11 flex-1 rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.items.remark_placeholder') }}">
                        <x-connect.button wire:click="addItem">{{ __('order_checking.items.add_custom') }}</x-connect.button>
                    </div>
                </div>
            </x-connect.card>

            <x-connect.card :title="__('order_checking.reference.title')" :description="__('order_checking.reference.description')">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">{{ __('order_checking.reference.reference_label') }}</label>
                        <input wire:model.blur="clientReferenceNo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('clientReferenceNo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('order_checking.reference.sender_label') }}</p>
                        <p class="mt-2 font-semibold text-connect-navy-900">{{ $mockSender['name'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $mockSender['branch'] }} · {{ $mockSender['code'] }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-sm font-semibold text-slate-700">{{ __('order_checking.reference.remark_label') }}</label>
                    <textarea wire:model.blur="orderRemark" rows="3" class="connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="{{ __('order_checking.reference.remark_placeholder') }}"></textarea>
                    @error('orderRemark') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </x-connect.card>

            <x-connect.card :title="__('order_checking.review.title')" :description="__('order_checking.review.description')">
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-slate-500">{{ __('order_checking.review.receiver') }}</p>
                        <p class="mt-1 font-semibold text-connect-navy-900">{{ $selectedReceiver?->name ?? __('order_checking.review.missing') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-slate-500">{{ __('order_checking.review.reference') }}</p>
                        <p class="mt-1 font-semibold text-connect-navy-900">{{ $clientReferenceNo ?: __('order_checking.review.missing') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-slate-500">{{ __('order_checking.review.items') }}</p>
                        <p class="mt-1 font-semibold text-connect-navy-900">{{ trans_choice('order_checking.review.item_count', count($items), ['count' => count($items), 'quantity' => $this->totalQuantity]) }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-slate-500">{{ __('order_checking.review.status') }}</p>
                        <p class="mt-1 font-semibold {{ $readyForReview ? 'text-emerald-700' : 'text-amber-700' }}">{{ $readyForReview ? __('order_checking.review.ready') : __('order_checking.review.not_ready') }}</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                    <x-connect.button variant="secondary" wire:click="$set('showSuccessDialog', false)">{{ __('order_checking.review.save_draft') }}</x-connect.button>
                    <x-connect.button wire:click="confirmMockOrder" :disabled="! $readyForReview">{{ __('order_checking.review.submit') }}</x-connect.button>
                </div>
            </x-connect.card>
        </div>

        <aside class="space-y-4">
            <x-connect.card :title="__('order_checking.progress.title')">
                <div class="space-y-3">
                    @foreach ([
                        ['done' => (bool) $selectedReceiver, 'label' => __('order_checking.progress.receiver')],
                        ['done' => count($items) > 0, 'label' => __('order_checking.progress.items')],
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
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="font-semibold text-connect-navy-900">{{ __('order_checking.help.validation_title') }}</p>
                        <p class="mt-1">{{ __('order_checking.help.validation_body') }}</p>
                    </div>
                </div>
            </x-connect.card>
        </aside>
    </div>

    @if ($showSuccessDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="mock-success-title">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl font-bold text-emerald-700">✓</div>
                <h3 id="mock-success-title" class="mt-4 text-xl font-semibold text-connect-navy-900">{{ __('order_checking.success.title') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('order_checking.success.body') }}</p>
                <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">{{ __('order_checking.review.reference') }}</span>
                        <span class="font-semibold text-connect-navy-900">{{ $clientReferenceNo }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-4">
                        <span class="text-slate-500">{{ __('order_checking.success.initial_status') }}</span>
                        <span class="font-semibold text-amber-700">{{ __('order_checking.success.checking') }}</span>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <x-connect.button wire:click="closeSuccessDialog">{{ __('order_checking.success.close') }}</x-connect.button>
                </div>
            </div>
        </div>
    @endif
</div>
