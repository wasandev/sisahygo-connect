<div class="space-y-6">
    <x-connect.page-header :title="__('history.title')" :description="__('history.description')" :eyebrow="__('history.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('shipments')" variant="secondary" wire:navigate>{{ __('history.actions.open_shipments') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('history.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('history.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('history.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ $pageError }}</span>
                    <x-connect.button size="sm" variant="secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __('history.actions.retry') }}</x-connect.button>
                </div>
            </div>
        @endif

        <x-connect.card :title="__('history.filters.title')" :description="__('history.filters.description')">
            <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="{{ __('history.filters.preset_group') }}">
                @foreach ($datePresetOptions as $value => $label)
                    <button type="button" wire:click="selectDatePreset('{{ $value }}')" wire:loading.attr="disabled" wire:target="selectDatePreset" @class([
                        'connect-focus min-h-11 rounded-lg border px-3 py-2 text-sm font-semibold transition',
                        'border-connect-blue-600 bg-connect-blue-50 text-connect-blue-700' => $datePreset === $value,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $datePreset !== $value,
                    ])>{{ $label }}</button>
                @endforeach
            </div>

            <form wire:submit="search" class="grid gap-4 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end">
                <div>
                    <label for="history-date-from" class="text-sm font-semibold text-slate-700">{{ __('history.filters.date_from') }}</label>
                    <input id="history-date-from" type="date" wire:model.blur="dateFrom" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('dateFrom') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="history-date-to" class="text-sm font-semibold text-slate-700">{{ __('history.filters.date_to') }}</label>
                    <input id="history-date-to" type="date" wire:model.blur="dateTo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('dateTo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="history-status" class="text-sm font-semibold text-slate-700">{{ __('history.filters.status') }}</label>
                    <select id="history-status" wire:model.blur="status" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        <option value="">{{ __('history.filters.all_statuses') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="history-keyword" class="text-sm font-semibold text-slate-700">{{ __('history.filters.keyword') }}</label>
                    <input id="history-keyword" type="search" wire:model.blur="keyword" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('history.filters.keyword_placeholder') }}" autocomplete="off">
                    @error('keyword') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                    <x-connect.button type="submit" wire:loading.attr="disabled" wire:target="search">
                        <span wire:loading.remove wire:target="search">{{ __('history.actions.search') }}</span>
                        <span wire:loading wire:target="search">{{ __('history.actions.searching') }}</span>
                    </x-connect.button>
                    <x-connect.button variant="secondary" wire:click="clearFilters" wire:loading.attr="disabled" wire:target="clearFilters">{{ __('history.actions.clear') }}</x-connect.button>
                </div>
            </form>
        </x-connect.card>

        <div class="space-y-4">
            <x-connect.card :title="__('history.results.title')" padding="none">
                <div wire:loading.delay wire:target="search,refresh,clearFilters,nextPage,previousPage,selectDatePreset" class="border-b border-slate-100 px-5 py-3 text-sm text-connect-blue-700">
                        {{ __('history.loading') }}
                    </div>

                    @if ($historyItems === [])
                        <div class="p-6">
                            <x-connect.empty-state :title="__('history.empty.title')" :description="__('history.empty.description')">
                                <x-slot:actions>
                                    <x-connect.button size="sm" :href="route('order-checking')" wire:navigate>{{ __('history.actions.create_first') }}</x-connect.button>
                                </x-slot:actions>
                            </x-connect.empty-state>
                        </div>
                    @else
                        <div class="hidden bg-white lg:block">
                            <div class="grid grid-cols-[minmax(13rem,1.15fr)_minmax(9rem,0.85fr)_minmax(9rem,0.85fr)_7rem_7rem_auto] gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase text-slate-500">
                                <span>{{ __('history.fields.order') }}</span>
                                <span>{{ __('history.fields.receiver') }}</span>
                                <span>{{ __('history.fields.destination') }}</span>
                                <span>{{ __('history.fields.date') }}</span>
                                <span>{{ __('history.fields.status') }}</span>
                                <span class="text-right">{{ __('history.fields.action') }}</span>
                            </div>

                            <div class="divide-y divide-slate-100">
                                @foreach ($historyItems as $item)
                                    @php
                                        $trackingNo = data_get($item, 'tracking_no');
                                        $displayNo = data_get($item, 'order_header_no') ?: $trackingNo;
                                        $statusVariant = data_get($item, 'order_status_variant', 'neutral');
                                        $statusLabel = data_get($item, 'order_status_label', '-');
                                    @endphp

                                    <article wire:key="history-row-{{ $trackingNo ?? $loop->index }}" class="grid grid-cols-[minmax(13rem,1.15fr)_minmax(9rem,0.85fr)_minmax(9rem,0.85fr)_7rem_7rem_auto] items-center gap-3 px-4 py-3 text-sm transition hover:bg-slate-50">
                                        <div class="min-w-0">
                                            <a href="{{ route('shipments.show', $trackingNo) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $displayNo ?: '-' }}</a>
                                            <p class="mt-1 break-all text-xs font-medium text-slate-500">{{ __('history.fields.tracking_no') }}: {{ $trackingNo ?: '-' }}</p>
                                            @if (data_get($item, 'client_reference_no'))
                                                <p class="mt-0.5 break-all text-xs text-slate-500">{{ __('history.fields.client_reference_no') }}: {{ data_get($item, 'client_reference_no') }}</p>
                                            @endif
                                        </div>
                                        <p class="min-w-0 break-words text-slate-700">{{ data_get($item, 'receiver_name') ?: '-' }}</p>
                                        <p class="min-w-0 break-words text-slate-700">{{ data_get($item, 'destination_branch_name') ?: '-' }}</p>
                                        <p class="text-slate-600">{{ data_get($item, 'order_header_date') ?: '-' }}</p>
                                        <div><x-connect.badge :variant="$statusVariant">{{ $statusLabel }}</x-connect.badge></div>
                                        <div class="text-right"><x-connect.button size="sm" :href="route('shipments.show', $trackingNo)" wire:navigate>{{ __('history.actions.view_detail') }}</x-connect.button></div>
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100 lg:hidden">
                            @foreach ($historyItems as $item)
                                <article wire:key="history-card-{{ $item['tracking_no'] }}" class="p-5">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('shipments.show', $item['tracking_no']) }}" wire:navigate class="connect-focus break-all text-base font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $item['order_header_no'] ?: $item['tracking_no'] }}</a>
                                            <x-connect.badge :variant="$item['order_status_variant']">{{ $item['order_status_label'] }}</x-connect.badge>
                                        </div>
                                        <dl class="grid gap-2 text-sm text-slate-600">
                                            <div><dt class="text-xs text-slate-500">{{ __('history.fields.tracking_no') }}</dt><dd class="break-all font-medium text-connect-navy-900">{{ $item['tracking_no'] }}</dd></div>
                                            <div><dt class="text-xs text-slate-500">{{ __('history.fields.date') }}</dt><dd>{{ $item['order_header_date'] ?: '-' }}</dd></div>
                                            <div><dt class="text-xs text-slate-500">{{ __('history.fields.receiver') }}</dt><dd class="break-words">{{ $item['receiver_name'] ?: '-' }}</dd></div>
                                            <div><dt class="text-xs text-slate-500">{{ __('history.fields.destination') }}</dt><dd class="break-words">{{ $item['destination_branch_name'] ?: '-' }}</dd></div>
                                        </dl>
                                        <x-connect.button size="sm" :href="route('shipments.show', $item['tracking_no'])" wire:navigate>{{ __('history.actions.view_detail') }}</x-connect.button>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <p>
                                {{ __('history.pagination.page', ['page' => $meta['current_page'] ?? $page]) }}
                                @if (($meta['total'] ?? null) !== null)
                                    · {{ __('history.pagination.total', ['total' => $meta['total']]) }}
                                @endif
                            </p>
                            <div class="flex gap-2">
                                <x-connect.button variant="secondary" size="sm" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" :disabled="$page <= 1">{{ __('history.pagination.previous') }}</x-connect.button>
                                <x-connect.button variant="secondary" size="sm" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" :disabled="($meta['last_page'] ?? $page) <= $page">{{ __('history.pagination.next') }}</x-connect.button>
                            </div>
                        </div>
                    @endif
            </x-connect.card>

            <x-connect.card :title="__('history.recent_receivers.title')" :description="__('history.recent_receivers.description')" padding="none">
                <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse ($recentReceivers as $receiver)
                            <div wire:key="recent-receiver-{{ data_get($receiver, 'receiver_customer_id') ?? md5(data_get($receiver, 'name', '')) }}" class="rounded-md border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="break-words text-sm font-semibold leading-5 text-connect-navy-900">{{ data_get($receiver, 'name') }}</p>
                                <div class="mt-1 flex flex-wrap gap-x-2 gap-y-0.5 text-xs text-slate-500">
                                    <span>{{ __('history.recent_receivers.latest', ['date' => data_get($receiver, 'latest_order_date') ?: '-']) }}</span>
                                    <span>{{ trans_choice('history.recent_receivers.count', data_get($receiver, 'count', 0), ['count' => data_get($receiver, 'count', 0)]) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 sm:col-span-2 xl:col-span-3">{{ __('history.recent_receivers.empty') }}</p>
                        @endforelse
                    </div>
            </x-connect.card>

            <x-connect.card :title="__('history.recent_products.title')" :description="__('history.recent_products.description')" padding="none">
                <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($recentProducts as $product)
                        <div wire:key="recent-product-{{ data_get($product, 'product_id') ?? md5(data_get($product, 'product_name', '').data_get($product, 'unit_name', '')) }}" class="rounded-md border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="break-words text-sm font-semibold leading-5 text-connect-navy-900">{{ data_get($product, 'product_name') }}</p>
                            <div class="mt-1 flex flex-wrap gap-x-2 gap-y-0.5 text-xs text-slate-500">
                                <span>{{ data_get($product, 'unit_name') ?: '-' }}</span>
                                <span>{{ trans_choice('history.recent_products.count', data_get($product, 'count', 0), ['count' => data_get($product, 'count', 0)]) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500 sm:col-span-2 xl:col-span-3">{{ __('history.recent_products.empty') }}</p>
                    @endforelse
                </div>
            </x-connect.card>
        </div>
    @endif
</div>
