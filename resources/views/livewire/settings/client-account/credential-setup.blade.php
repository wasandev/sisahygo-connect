<x-connect.card :title="__('client_account.credential_setup.title')" :description="__('client_account.credential_setup.description')">
    @if ($setupState?->isReady())
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4" role="status">
            <h3 class="text-base font-semibold text-emerald-950">{{ __('client_account.credential_setup.ready.title') }}</h3>
            <ul class="mt-3 grid gap-2 text-sm text-emerald-900 sm:grid-cols-2">
                @foreach (__('client_account.credential_setup.ready.checklist') as $item)
                    <li class="flex gap-2"><span aria-hidden="true">✓</span><span>{{ $item }}</span></li>
                @endforeach
            </ul>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-connect.button :href="route('dashboard')" wire:navigate>{{ __('client_account.credential_setup.ready.dashboard') }}</x-connect.button>
                <x-connect.button :href="route('settings.client-account')" variant="secondary" wire:navigate>{{ __('client_account.credential_setup.ready.status') }}</x-connect.button>
            </div>
        </div>
    @endif

    @if ($setupState)
        <div class="mb-5">
            <x-connect.onboarding-progress :steps="$setupState->steps" />
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <p class="text-sm text-slate-500">{{ __('client_account.credential_setup.fields.environment') }}</p>
            <p class="mt-1 font-semibold text-connect-navy-900">{{ $environment }}</p>
        </div>
        <div>
            <p class="text-sm text-slate-500">{{ __('client_account.credential_setup.fields.status') }}</p>
            <p class="mt-1">
                <x-connect.badge :variant="$credential && $credential->isActive() ? 'success' : 'warning'">
                    {{ $credential && $credential->isActive() ? __('client_account.credential_setup.statuses.active') : __('client_account.credential_setup.statuses.missing') }}
                </x-connect.badge>
            </p>
        </div>
        <div>
            <p class="text-sm text-slate-500">{{ __('client_account.credential_setup.fields.fingerprint') }}</p>
            <p class="mt-1 break-all font-semibold text-connect-navy-900">{{ $fingerprint ?? __('client_account.not_available') }}</p>
        </div>
        <div>
            <p class="text-sm text-slate-500">{{ __('client_account.credential_setup.fields.last_used') }}</p>
            <p class="mt-1 font-semibold text-connect-navy-900">{{ $credential?->last_used_at?->format('Y-m-d H:i:s') ?? __('client_account.not_available') }}</p>
        </div>
    </div>

    <div class="mt-5 rounded-lg border border-sky-200 bg-sky-50 p-4">
        <h3 class="text-sm font-semibold text-sky-950">{{ __('client_account.credential_setup.instructions_title') }}</h3>
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-sky-950">
            @foreach (__('client_account.credential_setup.instruction_steps') as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
        <div class="mt-4 border-t border-sky-200 pt-3 text-sm leading-6 text-sky-950">
            {{ __('client_account.credential_setup.security_note') }}
        </div>
    </div>

    @if ($successMessage)
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">{{ $successMessage }}</div>
    @endif

    @if ($errorMessage)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert">{{ $errorMessage }}</div>
    @endif

    @if ($canManage)
        <form wire:submit="save" class="mt-5 space-y-4">
            <x-connect.input
                type="password"
                name="apiKey"
                wire:model.defer="apiKey"
                autocomplete="new-password"
                :label="__('client_account.credential_setup.fields.api_key')"
                :hint="$credential ? __('client_account.credential_setup.replacement_hint') : __('client_account.credential_setup.hint')"
            />
            @error('apiKey')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <x-connect.button type="submit" :variant="$credential ? 'warning' : 'primary'" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $credential ? __('client_account.credential_setup.actions.replace') : __('client_account.credential_setup.actions.save') }}</span>
                <span wire:loading wire:target="save">{{ __('client_account.credential_setup.actions.verifying') }}</span>
            </x-connect.button>
        </form>
    @else
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            {{ __('client_account.credential_setup.admin_required') }}
        </div>
    @endif
</x-connect.card>
