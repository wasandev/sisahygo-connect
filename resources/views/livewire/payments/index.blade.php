@php
    $currentClientAccount = app()->bound(\App\Domain\ClientAccount\Models\ClientAccount::class) ? app(\App\Domain\ClientAccount\Models\ClientAccount::class) : null;
    $activeFilterChips = $this->activeFilterChips();
    $hasActiveFilters = $this->hasActiveFilters();
@endphp

<div class="space-y-6">
    <x-connect.page-header :title="__('payment.center.title')" :description="__('payment.center.subtitle')" :eyebrow="__('payment.center.eyebrow')">
        <x-slot:actions>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('payment.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('payment.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="{{ __('payment.account.label') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-500">{{ __('payment.account.current') }}</p>
                <p class="mt-1 break-words text-lg font-bold text-connect-navy-900">{{ $currentClientAccount?->name ?? __('payment.fallback.empty') }}</p>
                <p class="mt-1 break-all text-sm text-slate-500">{{ $currentClientAccount?->code ?? __('payment.fallback.empty') }}</p>
            </div>
            <p class="text-sm text-slate-500" aria-live="polite">
                {{ __('payment.center.refreshed_at', ['time' => $lastRefreshedAt ?: __('payment.fallback.empty')]) }}
            </p>
        </div>
    </section>

    @if ($unavailable)
        <x-connect.card :title="__('payment.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert" aria-live="polite">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ $pageError }}</span>
                    <x-connect.button size="sm" variant="secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __('payment.actions.retry') }}</x-connect.button>
                </div>
            </div>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('payment.summary.label') }}">
            @foreach ([
                ['key' => 'record_count', 'variant' => 'blue', 'value' => $summary['record_count'] ?? null],
                ['key' => 'total_amount', 'variant' => 'success', 'value' => $summary['total_amount_display'] ?? null],
                ['key' => 'paid_record_count', 'variant' => 'success', 'value' => $summary['paid_record_count'] ?? null, 'href' => route('payments', ['payment_status' => 'paid'])],
                ['key' => 'outstanding_record_count', 'variant' => 'warning', 'value' => $summary['outstanding_record_count'] ?? null, 'href' => route('payments', ['payment_status' => 'outstanding'])],
            ] as $card)
                <x-connect.stat-card :label="__('payment.summary.'.$card['key'])" :value="$pageError ? __('payment.fallback.empty') : ($card['value'] ?? __('payment.fallback.empty'))" :variant="$card['variant']">
                    @if (isset($card['href']) && ! $pageError)
                        <a href="{{ $card['href'] }}" wire:navigate class="connect-focus mt-3 inline-flex text-xs font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ __('payment.actions.open_filtered') }}</a>
                    @endif
                </x-connect.stat-card>
            @endforeach
        </section>

        <x-connect.card :title="__('payment.filters.title')" :description="__('payment.filters.description')">
            <form wire:submit="search" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-4 xl:grid-cols-[repeat(6,minmax(0,1fr))_auto] xl:items-end">
                    <div>
                        <label for="payment-type" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.payment_type') }}</label>
                        <select id="payment-type" wire:model.blur="paymentType" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('payment.filters.all_types') }}</option>
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('paymentType') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="payment-status" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.payment_status') }}</label>
                        <select id="payment-status" wire:model.blur="paymentStatus" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            <option value="">{{ __('payment.filters.all_statuses') }}</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('paymentStatus') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="payment-date-from" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.date_from') }}</label>
                        <input id="payment-date-from" type="date" wire:model.blur="dateFrom" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('dateFrom') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="payment-date-to" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.date_to') }}</label>
                        <input id="payment-date-to" type="date" wire:model.blur="dateTo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('dateTo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="payment-order-no" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.order_header_no') }}</label>
                        <input id="payment-order-no" type="search" wire:model.defer="orderHeaderNo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" autocomplete="off">
                        @error('orderHeaderNo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="payment-client-reference" class="text-sm font-semibold text-slate-700">{{ __('payment.filters.client_reference_no') }}</label>
                        <input id="payment-client-reference" type="search" wire:model.defer="clientReferenceNo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm" autocomplete="off">
                        @error('clientReferenceNo') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row xl:flex-col">
                        <x-connect.button type="submit" wire:loading.attr="disabled" wire:target="search">{{ __('payment.actions.search') }}</x-connect.button>
                        @if ($hasActiveFilters)
                            <x-connect.button variant="secondary" wire:click="clearFilters" wire:loading.attr="disabled" wire:target="clearFilters">{{ __('payment.actions.clear') }}</x-connect.button>
                        @endif
                    </div>
                </div>

                @if ($activeFilterChips !== [])
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4" aria-label="{{ __('payment.filters.active') }}">
                        @foreach ($activeFilterChips as $chip)
                            <button type="button" wire:click="clearFilter('{{ $chip['key'] }}')" wire:loading.attr="disabled" wire:target="clearFilter" class="connect-focus inline-flex min-h-9 items-center gap-2 rounded-full bg-connect-blue-50 px-3 py-1 text-sm font-semibold text-connect-blue-700 ring-1 ring-connect-blue-100 hover:bg-connect-blue-100">
                                <span>{{ $chip['label'] }}</span>
                                <span aria-hidden="true">×</span>
                                <span class="sr-only">{{ __('payment.filters.clear_chip') }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </form>
        </x-connect.card>

        <x-connect.card :title="__('payment.list.results_title')" padding="none">
            <div wire:loading.delay wire:target="search,refresh,clearFilters,clearFilter,nextPage,previousPage" class="border-b border-slate-100 px-5 py-3 text-sm text-connect-blue-700" aria-live="polite">{{ __('payment.loading') }}</div>

            @if ($pageError)
                <div class="p-6 text-sm text-slate-500">{{ __('payment.errors.unexpected') }}</div>
            @elseif ($payments === [])
                <div class="p-6">
                    <x-connect.empty-state :title="__('payment.empty.title')" :description="__('payment.empty.description')" />
                </div>
            @else
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <caption class="sr-only">{{ __('payment.list.results_title') }}</caption>
                        <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.order_header_no') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.billing_date') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.type') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.payer') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.parties') }}</th>
                                <th scope="col" class="px-5 py-3 text-right">{{ __('payment.fields.total_amount') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.status') }}</th>
                                <th scope="col" class="px-5 py-3">{{ __('payment.fields.payment_date') }}</th>
                                <th scope="col" class="px-5 py-3 text-right">{{ __('payment.actions.view_detail') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($payments as $payment)
                                <tr wire:key="payment-row-{{ $payment['payment_identifier'] }}">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('payments.show', $payment['payment_identifier']) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $payment['order_header_no'] ?: $payment['payment_identifier'] }}</a>
                                        <p class="mt-1 break-all text-xs text-slate-500">{{ __('payment.fields.payment_identifier') }}: {{ $payment['payment_identifier'] }}</p>
                                        <p class="mt-1 break-all text-xs text-slate-500">{{ __('payment.fields.client_reference_no') }}: {{ $payment['client_reference_no'] ?: __('payment.fallback.empty') }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $payment['billing_date'] }}</td>
                                    <td class="px-5 py-4"><x-connect.badge variant="blue">{{ $payment['payment_type_label'] }}</x-connect.badge></td>
                                    <td class="px-5 py-4"><span class="font-semibold text-connect-navy-900">{{ $payment['payer_role_label'] }}</span><p class="mt-1 break-words text-xs text-slate-500">{{ $payment['payer_name'] ?: __('payment.fallback.empty') }}</p></td>
                                    <td class="px-5 py-4 text-slate-600">
                                        <span class="block break-words">{{ __('payment.fields.sender') }}: {{ data_get($payment, 'sender.name') ?: __('payment.fallback.empty') }}</span>
                                        <span class="block break-words text-xs text-slate-500">{{ __('payment.fields.receiver') }}: {{ data_get($payment, 'receiver.name') ?: __('payment.fallback.empty') }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold text-connect-navy-900">{{ $payment['total_amount_display'] }}</td>
                                    <td class="px-5 py-4"><x-connect.badge :variant="$payment['payment_status_variant']">{{ $payment['payment_status_label'] }}</x-connect.badge></td>
                                    <td class="px-5 py-4 text-slate-600">{{ $payment['payment_date'] }}</td>
                                    <td class="px-5 py-4 text-right"><x-connect.button size="sm" :href="route('payments.show', $payment['payment_identifier'])" wire:navigate>{{ __('payment.actions.view_detail') }}</x-connect.button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 bg-slate-50/70 p-3 lg:hidden">
                    @foreach ($payments as $payment)
                        <article wire:key="payment-card-{{ $payment['payment_identifier'] }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('payments.show', $payment['payment_identifier']) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700">{{ $payment['order_header_no'] ?: $payment['payment_identifier'] }}</a>
                                    <p class="mt-1 break-all text-xs text-slate-500">{{ __('payment.fields.payment_identifier') }}: {{ $payment['payment_identifier'] }}</p>
                                    <p class="mt-1 break-all text-xs text-slate-500">{{ __('payment.fields.client_reference_no') }}: {{ $payment['client_reference_no'] ?: __('payment.fallback.empty') }}</p>
                                </div>
                                <x-connect.badge :variant="$payment['payment_status_variant']">{{ $payment['payment_status_label'] }}</x-connect.badge>
                            </div>
                            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                <div><dt class="text-xs text-slate-500">{{ __('payment.fields.type') }}</dt><dd><x-connect.badge variant="blue">{{ $payment['payment_type_label'] }}</x-connect.badge></dd></div>
                                <div><dt class="text-xs text-slate-500">{{ __('payment.fields.payer') }}</dt><dd class="font-semibold text-connect-navy-900">{{ $payment['payer_role_label'] }} · {{ $payment['payer_name'] ?: __('payment.fallback.empty') }}</dd></div>
                                <div><dt class="text-xs text-slate-500">{{ __('payment.fields.total_amount') }}</dt><dd class="font-semibold text-connect-navy-900">{{ $payment['total_amount_display'] }}</dd></div>
                                <div><dt class="text-xs text-slate-500">{{ __('payment.fields.billing_date') }}</dt><dd class="font-semibold text-connect-navy-900">{{ $payment['billing_date'] }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs text-slate-500">{{ __('payment.fields.parties') }}</dt><dd class="break-words font-semibold text-connect-navy-900">{{ data_get($payment, 'sender.name') ?: __('payment.fallback.empty') }} / {{ data_get($payment, 'receiver.name') ?: __('payment.fallback.empty') }}</dd></div>
                            </dl>
                            <x-connect.button class="mt-4 w-full" size="sm" :href="route('payments.show', $payment['payment_identifier'])" wire:navigate>{{ __('payment.actions.view_detail') }}</x-connect.button>
                        </article>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                        <p>
                            {{ __('payment.pagination.page', ['page' => $meta['current_page'] ?? $page]) }}
                            @if (($meta['last_page'] ?? null) !== null)
                                · {{ __('payment.pagination.last_page', ['last' => $meta['last_page']]) }}
                            @endif
                            @if (($meta['total'] ?? null) !== null)
                                · {{ __('payment.pagination.total', ['total' => $meta['total']]) }}
                            @endif
                        </p>
                        <label class="flex items-center gap-2 text-sm">
                            <span>{{ __('payment.pagination.per_page') }}</span>
                            <select wire:model.blur="perPage" class="connect-focus min-h-10 rounded-lg border-slate-300 text-sm shadow-sm">
                                @foreach ($perPageOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <x-connect.button variant="secondary" size="sm" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" :disabled="$page <= 1">{{ __('payment.pagination.previous') }}</x-connect.button>
                        <x-connect.button variant="secondary" size="sm" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" :disabled="($meta['last_page'] ?? $page) <= $page">{{ __('payment.pagination.next') }}</x-connect.button>
                    </div>
                </div>
            @endif
        </x-connect.card>
    @endif
</div>
