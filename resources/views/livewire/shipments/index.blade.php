<div class="space-y-6">
    <x-connect.page-header :title="__('shipments.list.title')" :description="__('shipments.list.description')" :eyebrow="__('shipments.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('tracking')" variant="secondary" wire:navigate>{{ __('shipments.list.open_tracking') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('shipments.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('shipments.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('shipments.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                {{ $unavailableMessage }}
            </div>
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">{{ $pageError }}</div>
        @endif

        <x-connect.card :title="__('shipments.filters.title')" :description="__('shipments.filters.description')">
            <form wire:submit="search" class="grid gap-4 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end">
                <div>
                    <label for="shipment-date-from" class="text-sm font-semibold text-slate-700">{{ __('shipments.filters.date_from') }}</label>
                    <input id="shipment-date-from" type="date" wire:model.blur="dateFrom" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('dateFrom') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="shipment-date-to" class="text-sm font-semibold text-slate-700">{{ __('shipments.filters.date_to') }}</label>
                    <input id="shipment-date-to" type="date" wire:model.blur="dateTo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('dateTo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="shipment-status" class="text-sm font-semibold text-slate-700">{{ __('shipments.filters.status') }}</label>
                    <select id="shipment-status" wire:model.blur="status" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        <option value="">{{ __('shipments.filters.all_statuses') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="shipment-keyword" class="text-sm font-semibold text-slate-700">{{ __('shipments.filters.keyword') }}</label>
                    <input id="shipment-keyword" type="search" wire:model.blur="keyword" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('shipments.filters.keyword_placeholder') }}" autocomplete="off">
                    @error('keyword') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-2 sm:flex-row lg:flex-col">
                    <x-connect.button type="submit" wire:loading.attr="disabled" wire:target="search">
                        <span wire:loading.remove wire:target="search">{{ __('shipments.actions.search') }}</span>
                        <span wire:loading wire:target="search">{{ __('shipments.actions.searching') }}</span>
                    </x-connect.button>
                    <x-connect.button variant="secondary" wire:click="clearFilters" wire:loading.attr="disabled" wire:target="clearFilters">{{ __('shipments.actions.clear') }}</x-connect.button>
                </div>
            </form>
        </x-connect.card>

        <x-connect.card :title="__('shipments.list.results_title')" padding="none">
            <div wire:loading.delay wire:target="search,refresh,clearFilters,nextPage,previousPage" class="border-b border-slate-100 px-5 py-3 text-sm text-connect-blue-700">
                {{ __('shipments.loading') }}
            </div>

            @if ($shipments === [])
                <div class="p-6">
                    <x-connect.empty-state :title="__('shipments.empty.title')" :description="__('shipments.empty.description')" />
                </div>
            @else
                <div class="space-y-3 bg-slate-50/70 p-3 sm:p-4">
                    @foreach ($shipments as $shipment)
                        @php
                            $trackingNo = data_get($shipment, 'tracking_no');
                            $displayNo = data_get($shipment, 'order_header_no') ?: $trackingNo;
                            $statusVariant = data_get($shipment, 'order_status_variant', 'neutral');
                            $statusLabel = data_get($shipment, 'order_status_label', '-');
                        @endphp

                        <article wire:key="shipment-{{ $trackingNo ?? $loop->index }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-connect-blue-200 hover:shadow-md sm:p-5">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <a href="{{ route('shipments.show', $trackingNo) }}" wire:navigate class="connect-focus break-all text-base font-semibold text-connect-blue-700 hover:text-connect-blue-900">
                                                {{ $displayNo ?: '-' }}
                                            </a>
                                            <p class="mt-1 break-all text-xs font-medium text-slate-500">
                                                {{ __('shipments.fields.tracking_no') }}: {{ $trackingNo ?: '-' }}
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-2">
                                            <x-connect.badge :variant="$statusVariant">{{ $statusLabel }}</x-connect.badge>
                                        </div>
                                    </div>

                                    <dl class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 xl:grid-cols-4">
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <dt class="text-xs font-medium text-slate-500">{{ __('shipments.fields.date') }}</dt>
                                            <dd class="mt-1 font-semibold text-connect-navy-900">{{ data_get($shipment, 'order_header_date') ?: '-' }}</dd>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 sm:col-span-2 xl:col-span-1">
                                            <dt class="text-xs font-medium text-slate-500">{{ __('shipments.fields.receiver') }}</dt>
                                            <dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ data_get($shipment, 'receiver_name') ?: '-' }}</dd>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 sm:col-span-2 xl:col-span-1">
                                            <dt class="text-xs font-medium text-slate-500">{{ __('shipments.fields.destination') }}</dt>
                                            <dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ data_get($shipment, 'destination_branch_name') ?: '-' }}</dd>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <dt class="text-xs font-medium text-slate-500">{{ __('shipments.fields.order_header_no') }}</dt>
                                            <dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ data_get($shipment, 'order_header_no') ?: '-' }}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="flex shrink-0 sm:justify-end xl:self-stretch">
                                    <x-connect.button size="sm" :href="route('shipments.show', $trackingNo)" wire:navigate class="w-full sm:w-auto xl:self-center">{{ __('shipments.actions.view_detail') }}</x-connect.button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>
                        {{ __('shipments.pagination.page', ['page' => $meta['current_page'] ?? $page]) }}
                        @if (($meta['total'] ?? null) !== null)
                            · {{ __('shipments.pagination.total', ['total' => $meta['total']]) }}
                        @endif
                    </p>
                    <div class="flex gap-2">
                        <x-connect.button variant="secondary" size="sm" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" :disabled="$page <= 1">{{ __('shipments.pagination.previous') }}</x-connect.button>
                        <x-connect.button variant="secondary" size="sm" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" :disabled="($meta['last_page'] ?? $page) <= $page">{{ __('shipments.pagination.next') }}</x-connect.button>
                    </div>
                </div>
            @endif
        </x-connect.card>
    @endif
</div>
