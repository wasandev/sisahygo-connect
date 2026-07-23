<x-guest-layout>
    <div class="mb-6 text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-connect-orange-600">{{ __('onboarding.invitation.eyebrow') }}</p>
        <h1 class="mt-2 text-xl font-semibold text-connect-navy-900">{{ __('onboarding.invitation.title') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $companyName }}</p>
    </div>

    <x-connect.onboarding-progress class="mb-6" :steps="[
        ['label' => __('onboarding.progress.account_created'), 'complete' => true],
        ['label' => __('onboarding.progress.email_verified'), 'complete' => false],
        ['label' => __('onboarding.progress.client_account_connected'), 'complete' => false],
        ['label' => __('onboarding.progress.first_shipment'), 'complete' => false],
        ['label' => __('onboarding.progress.first_tracking'), 'complete' => false],
    ]" />

    <form method="POST" action="{{ route('invitation.activate', $token) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="company_name" value="{{ old('company_name', $companyName) }}">
        <div>
            <label for="email" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
            @error('email') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
            @error('password') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password_confirmation" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.password_confirmation') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
        </div>
        <x-connect.button type="submit" class="w-full">{{ __('onboarding.invitation.submit') }}</x-connect.button>
    </form>
</x-guest-layout>
