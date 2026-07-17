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

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="space-y-4">
                <x-connect.card :title="__('history.results.title')" padding="none">
                    <div wire:loading.delay wire:target="search,refresh,clearFilters,nextPage,previousPage,selectDatePreset" class="border-b border-slate-100 px-5 py-3 text-sm text-connect-blue-700">
                        {{ __('history.loading') }}
                    </div>

                    @if ($historyItems === [])
                        <div class="p-6">
                            <x-connect.empty-state :title="__('history.empty.title')" :description="__('history.empty.description')" />
                        </div>
                    @else
                        <div class="hidden overflow-x-auto lg:block">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-5 py-3">{{ __('history.fields.order') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('history.fields.date') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('history.fields.receiver') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('history.fields.destination') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('history.fields.status') }}</th>
                                        <th scope="col" class="px-5 py-3 text-right">{{ __('history.fields.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($historyItems as $item)
                                        <tr wire:key="history-row-{{ $item['tracking_no'] }}">
                                            <td class="px-5 py-4">
                                                <a href="{{ route('shipments.show', $item['tracking_no']) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $item['order_header_no'] ?: $item['tracking_no'] }}</a>
                                                <p class="mt-1 break-all text-xs text-slate-500">{{ __('history.fields.tracking_no') }}: {{ $item['tracking_no'] }}</p>
                                                @if ($item['client_reference_no'])
                                                    <p class="mt-0.5 break-all text-xs text-slate-500">{{ __('history.fields.client_reference_no') }}: {{ $item['client_reference_no'] }}</p>
                                                @endif
                                            </td>
                                            <td class="px-5 py-4 text-slate-600">{{ $item['order_header_date'] ?: '-' }}</td>
                                            <td class="max-w-xs px-5 py-4"><span class="break-words text-slate-700">{{ $item['receiver_name'] ?: '-' }}</span></td>
                                            <td class="max-w-xs px-5 py-4"><span class="break-words text-slate-700">{{ $item['destination_branch_name'] ?: '-' }}</span></td>
                                            <td class="px-5 py-4"><x-connect.badge :variant="$item['order_status_variant']">{{ $item['order_status_label'] }}</x-connect.badge></td>
                                            <td class="px-5 py-4 text-right"><x-connect.button size="sm" :href="route('shipments.show', $item['tracking_no'])" wire:navigate>{{ __('history.actions.view_detail') }}</x-connect.button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
            </div>

            <aside class="space-y-4">
                <x-connect.card :title="__('history.recent_receivers.title')" :description="__('history.recent_receivers.description')">
                    @forelse ($recentReceivers as $receiver)
                        <div wire:key="recent-receiver-{{ $receiver['receiver_customer_id'] ?? md5($receiver['name']) }}" class="border-b border-slate-100 py-3 first:pt-0 last:border-b-0 last:pb-0">
                            <p class="break-words text-sm font-semibold text-connect-navy-900">{{ $receiver['name'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ __('history.recent_receivers.latest', ['date' => $receiver['latest_order_date'] ?: '-']) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ trans_choice('history.recent_receivers.count', $receiver['count'], ['count' => $receiver['count']]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('history.recent_receivers.empty') }}</p>
                    @endforelse
                </x-connect.card>

                <x-connect.card :title="__('history.recent_products.title')" :description="__('history.recent_products.description')">
                    @forelse ($recentProducts as $product)
                        <div wire:key="recent-product-{{ $product['product_id'] ?? md5($product['product_name'].$product['unit_name']) }}" class="border-b border-slate-100 py-3 first:pt-0 last:border-b-0 last:pb-0">
                            <p class="break-words text-sm font-semibold text-connect-navy-900">{{ $product['product_name'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $product['unit_name'] ?: '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ trans_choice('history.recent_products.count', $product['count'], ['count' => $product['count']]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('history.recent_products.empty') }}</p>
                    @endforelse
                </x-connect.card>
            </aside>
        </div>
    @endif
</div>
