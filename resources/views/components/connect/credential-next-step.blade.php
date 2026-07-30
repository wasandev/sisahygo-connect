@props([
    'setupState' => null,
    'compact' => false,
])

@php
    $isReady = is_array($setupState) ? (bool) ($setupState['is_ready'] ?? false) : ($setupState?->isReady() ?? false);
    $canManage = is_array($setupState) ? (bool) ($setupState['can_manage_settings'] ?? false) : ($setupState?->canManageSettings ?? false);
    $completedSteps = is_array($setupState) ? (int) ($setupState['completed_steps'] ?? 0) : ($setupState?->completedSteps() ?? 0);
    $totalSteps = is_array($setupState) ? (int) ($setupState['total_steps'] ?? 0) : ($setupState?->totalSteps() ?? 0);
@endphp

@if ($setupState && ! $isReady)
    <x-connect.card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold text-connect-navy-900">{{ __('onboarding.welcome.credential_setup.title') }}</h2>
                    <x-connect.badge variant="warning">{{ $completedSteps }}/{{ $totalSteps }}</x-connect.badge>
                </div>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    {{ $canManage ? __('onboarding.welcome.credential_setup.owner_description') : __('onboarding.welcome.credential_setup.member_description') }}
                </p>
            </div>
            @if ($canManage)
                <x-connect.button :href="route('settings.client-account')" variant="warning" wire:navigate>{{ __('onboarding.welcome.credential_setup.action') }}</x-connect.button>
            @endif
        </div>
    </x-connect.card>
@endif
