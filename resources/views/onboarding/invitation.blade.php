<x-guest-layout>
    @if ($errorMessage)
        <div class="text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-700">!</div>
            <h1 class="mt-4 text-xl font-semibold text-connect-navy-900">{{ __('onboarding.invitation.unavailable_title') }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $errorMessage }}</p>
            <x-connect.button :href="route('request-access')" class="mt-6 w-full">{{ __('onboarding.invitation.request_again') }}</x-connect.button>
        </div>
    @else
        <div class="mb-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-connect-orange-600">{{ __('onboarding.invitation.eyebrow') }}</p>
            <h1 class="mt-2 text-xl font-semibold text-connect-navy-900">{{ __('onboarding.invitation.title') }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $invitation->clientAccountName ?: $invitation->companyName }}</p>
        </div>

        <x-connect.onboarding-progress class="mb-6" :steps="[
            ['label' => __('onboarding.progress.account_created'), 'complete' => false],
            ['label' => __('onboarding.progress.email_verified'), 'complete' => $invitation->emailVerifiedByInvitation],
            ['label' => __('onboarding.progress.client_account_connected'), 'complete' => false],
            ['label' => __('onboarding.progress.first_shipment'), 'complete' => false],
            ['label' => __('onboarding.progress.first_tracking'), 'complete' => false],
        ]" />

        <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <p class="font-semibold text-connect-navy-900">{{ __('onboarding.invitation.summary_title') }}</p>
            <dl class="mt-3 space-y-2">
                <div>
                    <dt class="font-medium text-slate-600">{{ __('onboarding.fields.company_name') }}</dt>
                    <dd class="break-words text-connect-navy-900">{{ $invitation->clientAccountName ?: $invitation->companyName }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-600">{{ __('onboarding.fields.email') }}</dt>
                    <dd class="break-words text-connect-navy-900">{{ $invitation->email }}</dd>
                </div>
            </dl>
        </div>

        <form method="POST" action="{{ route('invitation.activate', $token) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ $invitation->email }}">
            <div>
                <label class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.email') }}</label>
                <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">{{ $invitation->email }}</div>
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
    @endif
</x-guest-layout>
