@php
    $summaryCards = $dashboard['summary_cards'] ?? [];
    $latestShipments = $dashboard['latest_shipments'] ?? [];
    $attentionShipments = $dashboard['attention_shipments'] ?? [];
    $recentReceivers = $dashboard['recent_receivers'] ?? [];
    $recentProducts = $dashboard['recent_products'] ?? [];
    $paymentOverview = $dashboard['payment_overview'] ?? ['available' => false, 'summary' => null, 'recent' => [], 'links' => [], 'cache' => []];
    $paymentCache = $paymentOverview['cache'] ?? [];
    $canCreateOrder = (bool) ($dashboard['can_create_order'] ?? false);
    $userName = auth()->user()?->name ?: __('dashboard.account.current');
@endphp

<div class="space-y-6">
    <x-connect.page-header :title="__('dashboard.title')" :description="__('dashboard.description')" :eyebrow="__('dashboard.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('history')" variant="secondary" wire:navigate>{{ __('dashboard.actions.open_history') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('dashboard.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('dashboard.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('dashboard.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <span>{{ $pageError }}</span>
                    <x-connect.button size="sm" variant="secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __('dashboard.actions.retry') }}</x-connect.button>
                </div>
            </div>
        @endif

        @if ($dashboard)
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-label="{{ __('dashboard.account.label') }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-connect-blue-700">{{ __('dashboard.greeting', ['name' => $userName]) }}</p>
                        <p class="mt-2 text-sm font-semibold text-slate-500">{{ __('dashboard.account.current') }}</p>
                        <h2 class="mt-1 break-words text-xl font-bold text-connect-navy-900">{{ $dashboard['client_account']['name'] ?? '-' }}</h2>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ $dashboard['client_account']['code'] ?? '-' }}</p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @if ($canCreateOrder)
                            <x-connect.button :href="route('order-checking')" wire:navigate>{{ __('dashboard.shortcuts.order_checking') }}</x-connect.button>
                        @else
                            <span class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-sm font-semibold text-slate-500" aria-disabled="true">{{ __('dashboard.shortcuts.order_checking_disabled') }}</span>
                        @endif
                        <x-connect.button :href="route('shipments')" variant="secondary" wire:navigate>{{ __('dashboard.shortcuts.shipments') }}</x-connect.button>
                        <x-connect.button :href="route('tracking')" variant="secondary" wire:navigate>{{ __('dashboard.shortcuts.tracking') }}</x-connect.button>
                        <x-connect.button :href="route('history')" variant="secondary" wire:navigate>{{ __('dashboard.shortcuts.history') }}</x-connect.button>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-500">
                    {{ __('dashboard.account.refreshed_at', ['time' => $dashboard['generated_at'] ?? '-']) }}
                </p>
            </section>

            <div wire:loading.delay wire:target="refresh" class="rounded-lg border border-connect-blue-100 bg-connect-blue-50 px-4 py-3 text-sm text-connect-blue-700">
                {{ __('dashboard.loading') }}
            </div>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('dashboard.metrics.label') }}">
                @foreach ($summaryCards as $card)
                    <x-connect.card>
                        <div class="flex min-h-36 flex-col justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-500">{{ __('dashboard.cards.'.$card['key'].'.label') }}</p>
                                @if ($card['available'])
                                    <p class="mt-3 text-3xl font-bold text-connect-navy-900">{{ number_format((int) $card['value']) }}</p>
                                @else
                                    <p class="mt-3 text-base font-semibold text-slate-500">{{ __('dashboard.cards.unavailable_value') }}</p>
                                @endif
                            </div>
                            <p class="text-sm leading-6 text-slate-500">{{ __('dashboard.cards.'.$card['key'].'.helper') }}</p>
                        </div>
                    </x-connect.card>
                @endforeach
            </section>


            <x-connect.card :title="__('dashboard.payments.title')" :description="__('dashboard.payments.description')" padding="none">
                <div wire:loading.delay wire:target="refresh" class="border-b border-slate-100 p-5 sm:p-6" role="status" aria-live="polite" aria-busy="true">
                    <p class="sr-only">{{ __('dashboard.payments.cache.loading') }}</p>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="h-4 w-24 animate-pulse rounded bg-slate-200"></div>
                                <div class="mt-3 h-7 w-32 animate-pulse rounded bg-slate-200"></div>
                            </div>
                        @endfor
                    </div>
                    <div class="mt-5 space-y-3">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="h-12 animate-pulse rounded bg-slate-100"></div>
                        @endfor
                    </div>
                </div>
                @if (! ($paymentOverview['available'] ?? false))
                    <div class="p-5 sm:p-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <span>{{ $paymentOverview['error'] ?? __('dashboard.payments.errors.unavailable') }}</span>
                                <x-connect.button size="sm" variant="secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __('dashboard.actions.retry') }}</x-connect.button>
                            </div>
                        </div>
                    </div>
                @else
                    @php
                        $paymentSummary = $paymentOverview['summary'] ?? [];
                        $recentPayments = $paymentOverview['recent'] ?? [];
                        $paymentLinks = $paymentOverview['links'] ?? [];
                    @endphp

                    @if (($paymentCache['is_stale'] ?? false) && ($paymentCache['cached_at'] ?? null))
                        <div class="border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-900" role="status" aria-live="polite">
                            {{ __('dashboard.payments.cache.stale_warning', ['time' => $paymentCache['cached_at']]) }}
                        </div>
                    @elseif (($paymentCache['status'] ?? null) === 'hit' && ($paymentCache['cached_at'] ?? null))
                        <div class="border-b border-slate-100 px-5 py-3 text-xs text-slate-500" role="status">
                            {{ __('dashboard.payments.cache.cached_at', ['time' => $paymentCache['cached_at']]) }}
                        </div>
                    @endif

                    <div class="border-b border-slate-100 p-5 sm:p-6">
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('dashboard.payments.summary_label') }}">
                            <a href="{{ $paymentLinks['all'] ?? route('payments') }}" wire:navigate class="connect-focus rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                                <p class="text-sm font-semibold text-slate-500">{{ __('dashboard.payments.summary.total_amount') }}</p>
                                <p class="mt-2 text-2xl font-bold text-connect-navy-900">{{ $paymentSummary['total_amount_display'] ?? '-' }}</p>
                            </a>
                            <a href="{{ $paymentLinks['outstanding'] ?? route('payments', ['payment_status' => 'outstanding']) }}" wire:navigate class="connect-focus rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                                <p class="text-sm font-semibold text-slate-500">{{ __('dashboard.payments.summary.outstanding_record_count') }}</p>
                                <p class="mt-2 text-2xl font-bold text-connect-navy-900">{{ $paymentSummary['outstanding_record_count'] ?? '-' }}</p>
                            </a>
                            <a href="{{ $paymentLinks['paid'] ?? route('payments', ['payment_status' => 'paid']) }}" wire:navigate class="connect-focus rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                                <p class="text-sm font-semibold text-slate-500">{{ __('dashboard.payments.summary.paid_record_count') }}</p>
                                <p class="mt-2 text-2xl font-bold text-connect-navy-900">{{ $paymentSummary['paid_record_count'] ?? '-' }}</p>
                            </a>
                            <a href="{{ $paymentLinks['all'] ?? route('payments') }}" wire:navigate class="connect-focus rounded-lg border border-slate-100 bg-slate-50 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                                <p class="text-sm font-semibold text-slate-500">{{ __('dashboard.payments.summary.record_count') }}</p>
                                <p class="mt-2 text-2xl font-bold text-connect-navy-900">{{ $paymentSummary['record_count'] ?? '-' }}</p>
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <h3 class="text-base font-semibold text-connect-navy-900">{{ __('dashboard.payments.recent_title') }}</h3>
                        <x-connect.button size="sm" variant="secondary" :href="$paymentLinks['all'] ?? route('payments')" wire:navigate>{{ __('dashboard.payments.view_all') }}</x-connect.button>
                    </div>

                    @if ($recentPayments === [])
                        <div class="p-6">
                            <x-connect.empty-state :title="__('dashboard.payments.empty_title')" :description="__('dashboard.payments.empty_description')" />
                        </div>
                    @else
                        <div class="hidden overflow-x-auto lg:block">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <caption class="sr-only">{{ __('dashboard.payments.recent_title') }}</caption>
                                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500">
                                    <tr>
                                        <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.order') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('payment.fields.type') }}</th>
                                        <th scope="col" class="px-5 py-3 text-right">{{ __('payment.fields.total_amount') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('payment.fields.status') }}</th>
                                        <th scope="col" class="px-5 py-3">{{ __('payment.fields.billing_date') }}</th>
                                        <th scope="col" class="px-5 py-3 text-right">{{ __('dashboard.fields.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($recentPayments as $payment)
                                        <tr wire:key="dashboard-payment-row-{{ $payment['payment_identifier'] }}">
                                            <td class="px-5 py-4"><a href="{{ route('payments.show', $payment['payment_identifier']) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $payment['order_header_no'] ?: $payment['payment_identifier'] }}</a></td>
                                            <td class="px-5 py-4"><x-connect.badge variant="blue">{{ $payment['payment_type_label'] }}</x-connect.badge></td>
                                            <td class="px-5 py-4 text-right font-semibold text-connect-navy-900">{{ $payment['total_amount_display'] }}</td>
                                            <td class="px-5 py-4"><x-connect.badge :variant="$payment['payment_status_variant']">{{ $payment['payment_status_label'] }}</x-connect.badge></td>
                                            <td class="px-5 py-4 text-slate-600">{{ $payment['billing_date'] }}</td>
                                            <td class="px-5 py-4 text-right"><x-connect.button size="sm" :href="route('payments.show', $payment['payment_identifier'])" wire:navigate>{{ __('dashboard.actions.view_detail') }}</x-connect.button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="divide-y divide-slate-100 lg:hidden">
                            @foreach ($recentPayments as $payment)
                                <article wire:key="dashboard-payment-card-{{ $payment['payment_identifier'] }}" class="p-5">
                                    <div class="flex flex-col gap-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('payments.show', $payment['payment_identifier']) }}" wire:navigate class="connect-focus break-all text-base font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $payment['order_header_no'] ?: $payment['payment_identifier'] }}</a>
                                            <x-connect.badge :variant="$payment['payment_status_variant']">{{ $payment['payment_status_label'] }}</x-connect.badge>
                                        </div>
                                        <dl class="grid gap-2 text-sm text-slate-600">
                                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.type') }}</dt><dd><x-connect.badge variant="blue">{{ $payment['payment_type_label'] }}</x-connect.badge></dd></div>
                                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.total_amount') }}</dt><dd class="font-semibold text-connect-navy-900">{{ $payment['total_amount_display'] }}</dd></div>
                                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.billing_date') }}</dt><dd>{{ $payment['billing_date'] }}</dd></div>
                                        </dl>
                                        <x-connect.button size="sm" :href="route('payments.show', $payment['payment_identifier'])" wire:navigate>{{ __('dashboard.actions.view_detail') }}</x-connect.button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                @endif
            </x-connect.card>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <x-connect.card :title="__('dashboard.latest.title')" :description="__('dashboard.latest.description')" padding="none">
                        @if ($latestShipments === [])
                            <div class="p-6">
                                <x-connect.empty-state :title="__('dashboard.latest.empty_title')" :description="__('dashboard.latest.empty_description')" />
                            </div>
                        @else
                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.order') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.date') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.receiver') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.destination') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('dashboard.fields.status') }}</th>
                                            <th scope="col" class="px-5 py-3 text-right">{{ __('dashboard.fields.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($latestShipments as $shipment)
                                            <tr wire:key="dashboard-latest-row-{{ $shipment['tracking_no'] }}">
                                                <td class="px-5 py-4">
                                                    <a href="{{ route('shipments.show', $shipment['tracking_no']) }}" wire:navigate class="connect-focus break-all font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $shipment['order_header_no'] ?: $shipment['tracking_no'] }}</a>
                                                    <p class="mt-1 break-all text-xs text-slate-500">{{ __('dashboard.fields.tracking_no') }}: {{ $shipment['tracking_no'] }}</p>
                                                </td>
                                                <td class="px-5 py-4 text-slate-600">{{ $shipment['order_header_date'] ?: '-' }}</td>
                                                <td class="max-w-xs px-5 py-4"><span class="break-words text-slate-700">{{ $shipment['receiver_name'] ?: '-' }}</span></td>
                                                <td class="max-w-xs px-5 py-4"><span class="break-words text-slate-700">{{ $shipment['destination_branch_name'] ?: '-' }}</span></td>
                                                <td class="px-5 py-4"><x-connect.badge :variant="$shipment['order_status_variant']">{{ $shipment['order_status_label'] }}</x-connect.badge></td>
                                                <td class="px-5 py-4 text-right"><x-connect.button size="sm" :href="route('shipments.show', $shipment['tracking_no'])" wire:navigate>{{ __('dashboard.actions.view_detail') }}</x-connect.button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="divide-y divide-slate-100 lg:hidden">
                                @foreach ($latestShipments as $shipment)
                                    <article wire:key="dashboard-latest-card-{{ $shipment['tracking_no'] }}" class="p-5">
                                        <div class="flex flex-col gap-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('shipments.show', $shipment['tracking_no']) }}" wire:navigate class="connect-focus break-all text-base font-semibold text-connect-blue-700 hover:text-connect-blue-900">{{ $shipment['order_header_no'] ?: $shipment['tracking_no'] }}</a>
                                                <x-connect.badge :variant="$shipment['order_status_variant']">{{ $shipment['order_status_label'] }}</x-connect.badge>
                                            </div>
                                            <dl class="grid gap-2 text-sm text-slate-600">
                                                <div><dt class="text-xs text-slate-500">{{ __('dashboard.fields.tracking_no') }}</dt><dd class="break-all font-medium text-connect-navy-900">{{ $shipment['tracking_no'] }}</dd></div>
                                                <div><dt class="text-xs text-slate-500">{{ __('dashboard.fields.date') }}</dt><dd>{{ $shipment['order_header_date'] ?: '-' }}</dd></div>
                                                <div><dt class="text-xs text-slate-500">{{ __('dashboard.fields.receiver') }}</dt><dd class="break-words">{{ $shipment['receiver_name'] ?: '-' }}</dd></div>
                                                <div><dt class="text-xs text-slate-500">{{ __('dashboard.fields.destination') }}</dt><dd class="break-words">{{ $shipment['destination_branch_name'] ?: '-' }}</dd></div>
                                            </dl>
                                            <x-connect.button size="sm" :href="route('shipments.show', $shipment['tracking_no'])" wire:navigate>{{ __('dashboard.actions.view_detail') }}</x-connect.button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </x-connect.card>

                    <x-connect.card :title="__('dashboard.attention.title')" :description="__('dashboard.attention.description')">
                        <div class="space-y-3">
                            @forelse ($attentionShipments as $shipment)
                                <article wire:key="dashboard-attention-{{ $shipment['tracking_no'] }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <a href="{{ route('shipments.show', $shipment['tracking_no']) }}" wire:navigate class="connect-focus break-all text-sm font-semibold text-amber-900 hover:text-amber-700">{{ $shipment['order_header_no'] ?: $shipment['tracking_no'] }}</a>
                                            <p class="mt-1 break-words text-sm text-amber-800">{{ $shipment['receiver_name'] ?: '-' }}</p>
                                        </div>
                                        <x-connect.badge :variant="$shipment['order_status_variant']">{{ $shipment['order_status_label'] }}</x-connect.badge>
                                    </div>
                                </article>
                            @empty
                                <p class="text-sm text-slate-500">{{ __('dashboard.attention.empty') }}</p>
                            @endforelse
                        </div>
                    </x-connect.card>
                </div>

                <aside class="space-y-4">
                    <x-connect.card :title="__('dashboard.recent_receivers.title')" :description="__('dashboard.recent_receivers.description')">
                        @forelse ($recentReceivers as $receiver)
                            <div wire:key="dashboard-receiver-{{ $receiver['receiver_customer_id'] ?? md5($receiver['name']) }}" class="border-b border-slate-100 py-3 first:pt-0 last:border-b-0 last:pb-0">
                                <p class="break-words text-sm font-semibold text-connect-navy-900">{{ $receiver['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('dashboard.recent_receivers.latest', ['date' => $receiver['latest_order_date'] ?: '-']) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ trans_choice('dashboard.recent_receivers.count', $receiver['count'], ['count' => $receiver['count']]) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('dashboard.recent_receivers.empty') }}</p>
                        @endforelse
                    </x-connect.card>

                    <x-connect.card :title="__('dashboard.recent_products.title')" :description="__('dashboard.recent_products.description')">
                        @forelse ($recentProducts as $product)
                            <div wire:key="dashboard-product-{{ $product['product_id'] ?? md5($product['product_name'].$product['unit_name']) }}" class="border-b border-slate-100 py-3 first:pt-0 last:border-b-0 last:pb-0">
                                <p class="break-words text-sm font-semibold text-connect-navy-900">{{ $product['product_name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $product['unit_name'] ?: '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ trans_choice('dashboard.recent_products.count', $product['count'], ['count' => $product['count']]) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('dashboard.recent_products.empty') }}</p>
                        @endforelse
                    </x-connect.card>
                </aside>
            </div>
        @endif
    @endif
</div>
