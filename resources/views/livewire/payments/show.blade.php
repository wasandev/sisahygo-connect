<div class="space-y-6">
    <x-connect.page-header :title="__('payment.detail.title')" :description="__('payment.detail.description')" :eyebrow="__('payment.center.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('payments')" variant="secondary" wire:navigate>{{ __('payment.actions.back_to_list') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">{{ __('payment.actions.refresh') }}</x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('payment.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @elseif ($notFound)
        <x-connect.card>
            <x-connect.empty-state :title="__('payment.detail.not_found_title')" :description="__('payment.detail.not_found_description')" />
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">{{ $pageError }}</div>
        @endif

        @if ($payment)
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <x-connect.card :title="$payment['payment_identifier']">
                        <div class="flex flex-wrap gap-2">
                            <x-connect.badge variant="blue">{{ $payment['payment_type_label'] }}</x-connect.badge>
                            <x-connect.badge :variant="$payment['payment_status_variant']">{{ $payment['payment_status_label'] }}</x-connect.badge>
                            <x-connect.badge>{{ $payment['payer_role_label'] }}</x-connect.badge>
                        </div>
                    </x-connect.card>

                    <x-connect.card :title="__('payment.detail.order_title')">
                        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.order_header_no') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $payment['order_header_no'] ?: __('payment.fallback.empty') }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.order_header_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['order_header_date'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.client_reference_no') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $payment['client_reference_no'] ?: __('payment.fallback.empty') }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.tracking_reference') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $payment['tracking_reference'] ?: __('payment.fallback.empty') }}</dd></div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('payment.detail.parties_title')">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div class="@if ($payment['payer_role'] === 'sender') rounded-lg bg-connect-blue-50 p-4 @endif">
                                <dt class="text-xs text-slate-500">{{ __('payment.fields.sender') }}</dt>
                                <dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ data_get($payment, 'sender.name') ?: __('payment.fallback.empty') }}</dd>
                                @if ($payment['payer_role'] === 'sender') <p class="mt-2 text-xs font-semibold text-connect-blue-700">{{ __('payment.fields.payer') }}</p> @endif
                            </div>
                            <div class="@if ($payment['payer_role'] === 'receiver') rounded-lg bg-connect-blue-50 p-4 @endif">
                                <dt class="text-xs text-slate-500">{{ __('payment.fields.receiver') }}</dt>
                                <dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ data_get($payment, 'receiver.name') ?: __('payment.fallback.empty') }}</dd>
                                @if ($payment['payer_role'] === 'receiver') <p class="mt-2 text-xs font-semibold text-connect-blue-700">{{ __('payment.fields.payer') }}</p> @endif
                            </div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('payment.detail.payment_title')">
                        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.billing_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['billing_date'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.payment_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['payment_date'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.total_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['total_amount_display'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.paid_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['paid_amount_display'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.outstanding_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['outstanding_amount_display'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.discount_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['discount_amount_display'] }}</dd></div>
                            <div><dt class="text-xs text-slate-500">{{ __('payment.fields.tax_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $payment['tax_amount_display'] }}</dd></div>
                        </dl>
                    </x-connect.card>
                </div>

                <aside class="space-y-4">
                    <x-connect.card :title="__('payment.detail.references_title')">
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-slate-500">{{ __('payment.fields.invoice_number') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ data_get($payment, 'invoice.number') ?: __('payment.fallback.empty') }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('payment.fields.invoice_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ data_get($payment, 'invoice.date') }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('payment.fields.receipt_number') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ data_get($payment, 'receipt.number') ?: __('payment.fallback.empty') }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('payment.fields.receipt_date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ data_get($payment, 'receipt.date') }}</dd></div>
                        </dl>
                    </x-connect.card>
                </aside>
            </div>
        @endif
    @endif
</div>
