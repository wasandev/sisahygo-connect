@php($currentClientAccount = app()->bound(\App\Domain\ClientAccount\Models\ClientAccount::class) ? app(\App\Domain\ClientAccount\Models\ClientAccount::class) : null)
<div class="space-y-6">
    <x-connect.page-header :title="$definition['title']" :description="$definition['description']" :eyebrow="__('navigation.reports')">
        <x-slot:actions>
            @if ($canExport)<x-connect.button :href="$this->exportUrl()">{{ __('reports.actions.export') }}</x-connect.button>@endif
            <x-connect.button variant="secondary" wire:click="refresh" wire:loading.attr="disabled">{{ __('reports.actions.refresh') }}</x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-sm font-semibold text-slate-500">{{ __('reports.account.current') }}</p>
        <p class="mt-1 font-bold text-connect-navy-900">{{ $currentClientAccount?->name }} <span class="font-medium text-slate-500">{{ $currentClientAccount?->code }}</span></p>
        <p class="mt-2 text-sm text-slate-500">{{ __('reports.refreshed_at', ['time' => $lastRefreshedAt ?: '-']) }}</p>
    </section>

    @if ($unavailableMessage)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">{{ $unavailableMessage }}</div>
    @else
        @if ($pageError)<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $pageError }}</div>@endif

        <x-connect.card :title="__('reports.filters.title')">
            <form wire:submit="search" class="grid gap-4 lg:grid-cols-4 xl:grid-cols-6">
                <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.date_from') }}<input type="date" wire:model.blur="dateFrom" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.date_to') }}<input type="date" wire:model.blur="dateTo" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.relationship') }}<select wire:model.blur="relationship" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="all">{{ __('reports.relationship.all') }}</option><option value="sender">{{ __('reports.relationship.sender') }}</option><option value="receiver">{{ __('reports.relationship.receiver') }}</option></select></label>
                <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.status') }}<input type="search" wire:model.defer="status" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.search') }}<input type="search" wire:model.defer="search" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                @if ($report === 'receivers')
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.province') }}<input type="search" wire:model.defer="province" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.district') }}<input type="search" wire:model.defer="district" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.sub_district') }}<input type="search" wire:model.defer="subDistrict" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                @endif
                @if ($report === 'products')
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.product') }}<input type="search" wire:model.defer="product" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.unit') }}<input type="search" wire:model.defer="unit" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                @endif
                @if ($report === 'order-checkings')
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.type') }}<select wire:model.blur="type" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="">{{ __('reports.filters.all') }}</option><option value="single">{{ __('reports.type.single') }}</option><option value="bulk">{{ __('reports.type.bulk') }}</option></select></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.client_reference') }}<input type="search" wire:model.defer="clientReference" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.batch_reference') }}<input type="search" wire:model.defer="batchReference" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.pricing_status') }}<select wire:model.blur="pricingStatus" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="">{{ __('reports.filters.all') }}</option><option value="resolved">{{ __('reports.pricing.resolved') }}</option><option value="unresolved">{{ __('reports.pricing.unresolved') }}</option></select></label>
                @endif
                @if ($report === 'shipment-status')
                    <label class="flex min-h-11 items-center gap-2 self-end text-sm font-semibold text-slate-700"><input type="checkbox" wire:model.blur="onlyDelayed" class="connect-focus rounded border-slate-300 text-connect-blue-700">{{ __('reports.filters.only_delayed') }}</label>
                    <label class="flex min-h-11 items-center gap-2 self-end text-sm font-semibold text-slate-700"><input type="checkbox" wire:model.blur="onlyInProgress" class="connect-focus rounded border-slate-300 text-connect-blue-700">{{ __('reports.filters.only_in_progress') }}</label>
                @endif
                @if ($report === 'payments')
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.payment_status') }}<select wire:model.blur="paymentStatus" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="">{{ __('reports.filters.all') }}</option><option value="paid">{{ __('reports.payment_status.paid') }}</option><option value="unpaid">{{ __('reports.payment_status.unpaid') }}</option></select></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.payment_type') }}<select wire:model.blur="paymentType" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"><option value="">{{ __('reports.filters.all') }}</option>@foreach(['H','T','F','E','L'] as $pt)<option value="{{ $pt }}">{{ $pt }}</option>@endforeach</select></label>
                    <label class="text-sm font-semibold text-slate-700">{{ __('reports.filters.client_reference') }}<input type="search" wire:model.defer="clientReference" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm"></label>
                @endif
                <div class="flex items-end gap-2"><x-connect.button type="submit">{{ __('reports.actions.apply') }}</x-connect.button><x-connect.button variant="secondary" wire:click="clearFilters">{{ __('reports.actions.reset') }}</x-connect.button></div>
            </form>
            @foreach ($errors->all() as $error)<p class="mt-2 text-sm font-medium text-red-600">{{ $error }}</p>@endforeach
        </x-connect.card>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">@foreach ($definition['summary'] as $metric)<x-connect.stat-card :label="__('reports.fields.'.$metric)" :value="$summary[$metric] ?? '-'" variant="blue" />@endforeach</section>

        <x-connect.card :title="__('reports.table.title')" padding="none">
            <div wire:loading.delay class="border-b border-slate-100 px-5 py-3 text-sm text-connect-blue-700">{{ __('reports.loading') }}</div>
            @if ($rows === [])
                <div class="p-6"><x-connect.empty-state :title="__('reports.empty.title')" :description="__('reports.empty.description')" /></div>
            @else
                <div class="overflow-x-auto"><table class="min-w-[980px] divide-y divide-slate-100 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500"><tr>@foreach($definition['columns'] as $column)<th class="px-4 py-3">{{ __('reports.fields.'.$column) }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100 bg-white">@foreach($rows as $i => $row)<tr wire:key="report-row-{{ $i }}" class="align-top hover:bg-slate-50/70">@foreach($definition['columns'] as $column)<td class="px-4 py-3 text-slate-700">{{ $this->displayValue($row, $column) }}</td>@endforeach</tr>@if($report === 'shipment-status' && !empty($row['timeline']))<tr wire:key="report-row-{{ $i }}-timeline"><td colspan="{{ count($definition['columns']) }}" class="bg-slate-50 px-4 py-3"><details class="group"><summary class="cursor-pointer text-sm font-semibold text-connect-blue-700">{{ __('reports.timeline.expand') }}</summary><div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-4">@foreach($row['timeline'] as $event)<div class="rounded-lg border border-slate-200 bg-white p-3"><p class="font-semibold text-connect-navy-900">{{ __('reports.values.current_status.'.data_get($event, 'status'), [], app()->getLocale()) === 'reports.values.current_status.'.data_get($event, 'status') ? data_get($event, 'status') : __('reports.values.current_status.'.data_get($event, 'status')) }}</p><p class="mt-1 text-xs text-slate-500">{{ data_get($event, 'date') ?: '-' }} {{ data_get($event, 'time') ?: '' }}</p><p class="mt-1 text-xs text-slate-500">{{ data_get($event, 'user') ?: '-' }}</p>@if(data_get($event, 'remark'))<p class="mt-2 text-xs text-slate-600">{{ data_get($event, 'remark') }}</p>@endif</div>@endforeach</div></details></td></tr>@endif @endforeach</tbody></table></div>
                @if ($meta)<div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-sm text-slate-600"><span>{{ __('reports.pagination', ['page' => $meta['current_page'] ?? 1, 'last' => $meta['last_page'] ?? 1, 'total' => $meta['total'] ?? 0]) }}</span><div class="flex gap-2"><x-connect.button size="sm" variant="secondary" wire:click="previousPage">{{ __('reports.actions.previous') }}</x-connect.button><x-connect.button size="sm" variant="secondary" wire:click="nextPage">{{ __('reports.actions.next') }}</x-connect.button></div></div>@endif
            @endif
        </x-connect.card>
    @endif
</div>
