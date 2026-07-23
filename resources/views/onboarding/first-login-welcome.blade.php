<x-app-layout :title="__('onboarding.welcome.meta_title')">
    <div class="space-y-6">
        <x-connect.page-header :title="__('onboarding.welcome.title')" :description="__('onboarding.welcome.description')" :eyebrow="__('onboarding.welcome.eyebrow')" />

        <x-connect.card>
            <x-connect.onboarding-progress />
        </x-connect.card>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('onboarding.welcome.features_label') }}">
            @foreach ([
                __('onboarding.welcome.features.create_shipment'),
                __('onboarding.welcome.features.track_shipment'),
                __('onboarding.welcome.features.payment_center'),
                __('onboarding.welcome.features.history'),
            ] as $feature)
                <x-connect.card>
                    <div class="flex min-h-40 flex-col gap-3">
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
