<x-app-layout :title="__('onboarding.welcome.meta_title')">
    <div class="space-y-6">
        <x-connect.page-header :title="__('onboarding.welcome.title')" :description="__('onboarding.welcome.description')" :eyebrow="__('onboarding.welcome.eyebrow')" />

        @if ($setupState)
            <x-connect.card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-500">{{ __('onboarding.welcome.client_account') }}</p>
                        <h2 class="mt-1 break-words text-xl font-bold text-connect-navy-900">{{ $setupState->clientAccountName }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('onboarding.welcome.setup_summary') }}</p>
                    </div>
                    <x-connect.badge :variant="$setupState->isReady() ? 'success' : 'warning'">
                        {{ $setupState->isReady() ? __('onboarding.welcome.ready_badge') : __('onboarding.welcome.pending_badge', ['completed' => $setupState->completedSteps(), 'total' => $setupState->totalSteps()]) }}
                    </x-connect.badge>
                </div>
                <x-connect.onboarding-progress class="mt-5" :steps="$setupState->steps" />
            </x-connect.card>

            @if ($setupState->isReady())
                <x-connect.card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-connect-navy-900">{{ __('onboarding.welcome.ready.title') }}</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('onboarding.welcome.ready.description') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <x-connect.button :href="route('dashboard')" wire:navigate>{{ __('onboarding.welcome.ready.dashboard') }}</x-connect.button>
                            <x-connect.button :href="route('settings.client-account')" variant="secondary" wire:navigate>{{ __('onboarding.welcome.ready.status') }}</x-connect.button>
                        </div>
                    </div>
                </x-connect.card>
            @else
                <x-connect.credential-next-step :setup-state="$setupState" />
            @endif
        @else
            <x-connect.card>
                <p class="text-sm leading-6 text-slate-600">{{ __('client_account.empty_account') }}</p>
            </x-connect.card>
        @endif

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('onboarding.welcome.features_label') }}">
            @foreach ([
                __('onboarding.welcome.features.create_shipment'),
                __('onboarding.welcome.features.track_shipment'),
                __('onboarding.welcome.features.payment_center'),
                __('onboarding.welcome.features.history'),
            ] as $feature)
                <x-connect.card>
                    <div class="flex min-h-36 flex-col gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">✓</div>
                        <h2 class="text-base font-semibold text-connect-navy-900">{{ $feature['title'] }}</h2>
                        <p class="text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                    </div>
                </x-connect.card>
            @endforeach
        </section>

        <form method="POST" action="{{ route('onboarding.start') }}">
            @csrf
            <x-connect.button type="submit" size="lg">{{ __('onboarding.welcome.start') }}</x-connect.button>
        </form>
    </div>
</x-app-layout>
