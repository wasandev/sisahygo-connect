<x-app-layout :title="__('navigation.dashboard')">
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => __('page.dashboard.orders_today'), 'value' => '0'],
                ['label' => __('page.dashboard.in_transit'), 'value' => '0'],
                ['label' => __('page.dashboard.completed'), 'value' => '0'],
                ['label' => __('page.dashboard.needs_attention'), 'value' => '0'],
            ] as $stat)
                <x-connect.card>
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-connect-navy-900">{{ $stat['value'] }}</p>
                </x-connect.card>
            @endforeach
        </div>

        <x-connect.card :title="__('page.dashboard.workspace_overview')">
            <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
                <div>
                    <p class="text-sm leading-6 text-slate-600">
                        {{ __('page.dashboard.ready_message') }}
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <x-connect.button :href="route('order-checking')" wire:navigate>{{ __('page.dashboard.open_order_checking') }}</x-connect.button>
                        <x-connect.button :href="route('tracking')" wire:navigate variant="secondary">{{ __('page.dashboard.view_tracking') }}</x-connect.button>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-connect-navy-900">{{ __('page.dashboard.current_status') }}</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">{{ __('page.dashboard.authentication') }}</dt>
                            <dd class="font-semibold text-emerald-700">{{ __('page.dashboard.active') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">{{ __('page.dashboard.dashboard') }}</dt>
                            <dd class="font-semibold text-emerald-700">{{ __('page.dashboard.active') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">{{ __('page.dashboard.api_integration') }}</dt>
                            <dd class="font-semibold text-slate-600">{{ __('page.dashboard.pending') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </x-connect.card>
    </div>
</x-app-layout>
