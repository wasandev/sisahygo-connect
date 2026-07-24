@props([
    'steps' => null,
])

@php
    $steps ??= [
        ['label' => __('onboarding.progress.account_created'), 'complete' => true],
        ['label' => __('onboarding.progress.email_verified'), 'complete' => true],
        ['label' => __('onboarding.progress.client_account_connected'), 'complete' => true],
        ['label' => __('onboarding.progress.first_shipment'), 'complete' => false],
        ['label' => __('onboarding.progress.first_tracking'), 'complete' => false],
    ];
@endphp

<ol {{ $attributes->merge(['class' => 'grid gap-3 text-sm sm:grid-cols-5']) }} aria-label="{{ __('onboarding.progress.label') }}">
    @foreach ($steps as $step)
        @php $complete = (bool) ($step['complete'] ?? false); @endphp
        <li class="rounded-lg border px-3 py-3 {{ $complete ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600' }}">
            <span class="flex items-center gap-2 font-semibold">
                <span aria-hidden="true">{{ $complete ? '✓' : '○' }}</span>
                <span>{{ $step['label'] }}</span>
            </span>
        </li>
    @endforeach
</ol>
