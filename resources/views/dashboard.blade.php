<x-app-layout title="Dashboard">
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Orders today', 'value' => '0'],
                ['label' => 'In transit', 'value' => '0'],
                ['label' => 'Completed', 'value' => '0'],
                ['label' => 'Needs attention', 'value' => '0'],
            ] as $stat)
                <x-connect.card>
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-connect-navy-900">{{ $stat['value'] }}</p>
                </x-connect.card>
            @endforeach
        </div>

        <x-connect.card title="Workspace overview">
            <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
                <div>
                    <p class="text-sm leading-6 text-slate-600">
                        Sisahygo Connect is ready for authenticated workflows. Dashboard is active now, while the remaining modules are prepared as safe placeholders until the external Sisahygo API integration begins.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('order-checking') }}" wire:navigate>
                            <x-connect.button type="button">Open Order Checking</x-connect.button>
                        </a>
                        <a href="{{ route('tracking') }}" wire:navigate>
                            <x-connect.button type="button" variant="secondary">View Tracking</x-connect.button>
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-connect-navy-900">Current status</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Authentication</dt>
                            <dd class="font-semibold text-emerald-700">Active</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Dashboard</dt>
                            <dd class="font-semibold text-emerald-700">Active</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">API integration</dt>
                            <dd class="font-semibold text-slate-600">Pending</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </x-connect.card>
    </div>
</x-app-layout>