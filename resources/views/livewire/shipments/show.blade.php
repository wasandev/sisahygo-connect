<div class="space-y-6">
    <x-connect.page-header :title="__('shipments.detail.title')" :description="__('shipments.detail.description')" :eyebrow="__('shipments.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('shipments')" variant="secondary" wire:navigate>{{ __('shipments.actions.back_to_list') }}</x-connect.button>
            <x-connect.button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <span wire:loading.remove wire:target="refresh">{{ __('shipments.actions.refresh') }}</span>
                <span wire:loading wire:target="refresh">{{ __('shipments.actions.refreshing') }}</span>
            </x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    @if ($unavailable)
        <x-connect.card :title="__('shipments.unavailable.title')">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
        </x-connect.card>
    @elseif ($notFound)
        <x-connect.card>
            <x-connect.empty-state :title="__('shipments.detail.not_found_title')" :description="__('shipments.detail.not_found_description')" />
        </x-connect.card>
    @else
        @if ($pageError)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">{{ $pageError }}</div>
        @endif

        @if ($shipment)
            @php($summary = $shipment['summary'])
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-4">
                    <x-connect.card :title="__('shipments.detail.summary_title')">
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.tracking_no') }}</p><p class="mt-1 break-all font-semibold text-connect-navy-900">{{ $summary['tracking_no'] }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.order_header_no') }}</p><p class="mt-1 break-all font-semibold text-connect-navy-900">{{ $summary['order_header_no'] ?: '-' }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.status') }}</p><p class="mt-1"><x-connect.badge :variant="$summary['order_status_variant']">{{ $summary['order_status_label'] }}</x-connect.badge></p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.date') }}</p><p class="mt-1 font-semibold text-connect-navy-900">{{ $summary['order_header_date'] ?: '-' }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.sender') }}</p><p class="mt-1 break-words font-semibold text-connect-navy-900">{{ $summary['sender_name'] ?: '-' }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ __('shipments.fields.receiver') }}</p><p class="mt-1 break-words font-semibold text-connect-navy-900">{{ $summary['receiver_name'] ?: '-' }}</p></div>
                        </div>
                    </x-connect.card>

                    <x-connect.card :title="__('shipments.detail.items_title')" padding="none">
                        @if ($shipment['items'] === [])
                            <div class="p-6 text-sm text-slate-500">{{ __('shipments.detail.no_items') }}</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th scope="col" class="px-5 py-3">{{ __('shipments.fields.product') }}</th>
                                            <th scope="col" class="px-5 py-3">{{ __('shipments.fields.unit') }}</th>
                                            <th scope="col" class="px-5 py-3 text-right">{{ __('shipments.fields.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach ($shipment['items'] as $item)
                                            <tr wire:key="shipment-item-{{ $item['id'] ?? $loop->index }}">
                                                <td class="max-w-xs px-5 py-4"><p class="break-words font-medium text-connect-navy-900">{{ $item['product_name'] ?: '-' }}</p><p class="mt-1 break-words text-xs text-slate-500">{{ $item['remark'] ?: '' }}</p></td>
                                                <td class="px-5 py-4 text-slate-600">{{ $item['unit_name'] ?: '-' }}</td>
                                                <td class="px-5 py-4 text-right font-semibold text-connect-navy-900">{{ $item['amount'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-connect.card>
                </div>

                <aside class="space-y-4">
                    <x-connect.card :title="__('shipments.detail.timeline_title')">
                        @if ($shipment['timeline'] === [])
                            <p class="text-sm text-slate-500">{{ __('shipments.detail.no_timeline') }}</p>
                        @else
                            <x-connect.timeline :items="collect($shipment['timeline'])->map(fn ($item, $index) => [
                                'state' => $index === 0 ? 'current' : 'done',
                                'title' => $item['label'],
                                'meta' => trim(($item['occurred_at_display'] ?: '') . ' ' . ($item['branch_name'] ? '· '.$item['branch_name'] : '')),
                                'description' => $item['description'],
                            ])->all()" />
                        @endif
                    </x-connect.card>

                    <x-connect.card :title="__('shipments.detail.reference_title')">
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-slate-500">{{ __('shipments.fields.client_reference_no') }}</dt><dd class="mt-1 break-all font-semibold text-connect-navy-900">{{ $summary['client_reference_no'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('shipments.fields.branch') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $summary['branch_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('shipments.fields.destination') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $summary['destination_branch_name'] ?: '-' }}</dd></div>
                            <div><dt class="text-slate-500">{{ __('shipments.fields.remark') }}</dt><dd class="mt-1 break-words text-connect-navy-900">{{ $shipment['remark'] ?: '-' }}</dd></div>
                        </dl>
                    </x-connect.card>
                </aside>
            </div>
        @endif
    @endif
</div>
