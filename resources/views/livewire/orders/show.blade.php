<div class="space-y-6">
    <x-connect.page-header :title="__('orders.detail.title')" :description="__('orders.detail.description')" :eyebrow="__('orders.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('history')" variant="secondary" wire:navigate>{{ __('orders.actions.back_to_history') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('orders.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('orders.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('orders.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @elseif ($notFound)
        <x-connect.card>
            <x-connect.empty-state :title="__('orders.detail.not_found_title')" :description="__('orders.detail.not_found_description')" />
        </x-connect.card>
    @else
        @if ($pageError)
            <x-connect.toast variant="danger" :title="$pageError" />
        @endif

        <div wire:loading.delay wire:target="refresh">
            <x-connect.loading :label="__('orders.loading')" />
        </div>

        @if ($shipment)
            @php($summary = $shipment['summary'])
            <section class="grid gap-4 lg:grid-cols-4" aria-label="{{ __('orders.detail.summary_title') }}">
                <x-connect.stat-card :label="__('orders.fields.tracking_no')" :value="$summary['tracking_no']" />
                <x-connect.stat-card :label="__('orders.fields.order_header_no')" :value="$summary['order_header_no'] ?: '-'" />
                <x-connect.stat-card :label="__('orders.fields.total_amount')" :value="$summary['order_amount_display']" />
                <x-connect.card>
                    <p class="text-sm font-semibold text-slate-500">{{ __('orders.fields.status') }}</p>
                    <p class="mt-3"><x-connect.badge :variant="$summary['order_status_variant']">{{ $summary['order_status_label'] }}</x-connect.badge></p>
                </x-connect.card>
            </section>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <x-connect.card :title="__('orders.detail.order_information_title')">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.date') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['order_header_date'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.client_reference_no') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $summary['client_reference_no'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.order_type') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['order_type'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.payment_type') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['payment_type'] ?: '-' }}</dd></div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('orders.detail.receiver_title')">
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.receiver') }}</dt><dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ $summary['receiver_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.receiver_customer_id') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['receiver_customer_id'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.destination') }}</dt><dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ $summary['destination_branch_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-sm text-slate-500">{{ __('orders.fields.sender') }}</dt><dd class="mt-1 break-words font-semibold text-connect-navy-900">{{ $summary['sender_name'] ?: '-' }}</dd></div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('orders.detail.items_title')" padding="none">
                        @if ($shipment['items'] === [])
                            <div class="p-6"><x-connect.empty-state :title="__('orders.detail.no_items')" /></div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                        <tr>
                                            <th scope="col" class="px-5 py-3">{{ __('orders.fields.product') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('orders.fields.unit') }}</th>
                                            <th scope="col" class="px-5 py-3 text-right">{{ __('orders.fields.amount') }}</th>
                                            <th scope="col" class="px-5 py-3 text-right">{{ __('orders.fields.line_amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($shipment['items'] as $item)
                                            <tr wire:key="order-item-{{ $item['id'] ?? $loop->index }}">
                                                <td class="max-w-xs px-5 py-4"><p class="break-words font-medium text-connect-navy-900">{{ $item['product_name'] ?: '-' }}</p><p class="mt-1 break-words text-xs text-slate-500">{{ $item['remark'] ?: '' }}</p></td>
                                                <td class="px-5 py-4 text-slate-600">{{ $item['unit_name'] ?: '-' }}</td>
                                                <td class="px-5 py-4 text-right font-semibold text-connect-navy-900">{{ $item['amount'] ?: '-' }}</td>
                                                <td class="px-5 py-4 text-right font-semibold text-connect-navy-900">{{ $item['line_amount'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-connect.card>
                </div>

                <aside class="space-y-4">
                    <x-connect.card :title="__('orders.detail.freight_title')">
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-slate-500">{{ __('orders.fields.freight_amount') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['order_amount_display'] }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('orders.fields.payment_status') }}</dt><dd class="mt-1 font-semibold text-connect-navy-900">{{ $summary['payment_status'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('orders.fields.remark') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $shipment['remark'] ?: '-' }}</dd></div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('orders.detail.shipment_title')">
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-slate-500">{{ __('orders.fields.branch') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $summary['branch_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('orders.fields.destination') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $summary['destination_branch_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('orders.fields.tracking_no') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $summary['tracking_no'] }}</dd></div>
                        </dl>
                    </x-connect.card>

                    <x-connect.card :title="__('orders.detail.timeline_title')">
                        @if ($shipment['timeline'] === [])
                            <p class="text-sm text-slate-500">{{ __('orders.detail.no_timeline') }}</p>
                        @else
                            <x-connect.timeline :items="collect($shipment['timeline'])->map(fn ($item, $index) => [
                                'state' => $index === 0 ? 'current' : 'done',
                                'title' => $item['label'],
                                'meta' => trim(($item['occurred_at_display'] ?: '') . ' ' . ($item['branch_name'] ? '- '.$item['branch_name'] : '')),
                                'description' => $item['description'],
                            ])->all()" />
                        @endif
                    </x-connect.card>
                </aside>
            </div>
        @endif
    @endif
</div>
