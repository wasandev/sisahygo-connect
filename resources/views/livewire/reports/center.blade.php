@php($currentClientAccount = app()->bound(\App\Domain\ClientAccount\Models\ClientAccount::class) ? app(\App\Domain\ClientAccount\Models\ClientAccount::class) : null)
<div class="space-y-6">
    <x-connect.page-header :title="__('reports.center.title')" :description="__('reports.center.description')" :eyebrow="__('navigation.reports')" />
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-sm font-semibold text-slate-500">{{ __('reports.account.current') }}</p>
        <p class="mt-1 text-lg font-bold text-connect-navy-900">{{ $currentClientAccount?->name }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $currentClientAccount?->code }}</p>
    </section>
    <section class="grid gap-4 lg:grid-cols-3">
        @foreach ($reports as $key => $report)
            <x-connect.card>
                <div class="flex h-full flex-col gap-4">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-connect-blue-50 text-connect-blue-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16V9m4 7V7m4 9v-5" /></svg></div>
                    <div class="min-w-0 flex-1"><h2 class="text-lg font-bold text-connect-navy-900">{{ $report['title'] }}</h2><p class="mt-2 text-sm leading-6 text-slate-500">{{ $report['description'] }}</p></div>
                    <x-connect.button :href="route($report['route'])" wire:navigate>{{ __('reports.actions.open') }}</x-connect.button>
                </div>
            </x-connect.card>
        @endforeach
    </section>
</div>
