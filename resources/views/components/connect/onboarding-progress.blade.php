@props([
    'steps' => null,
])

@php
    $steps ??= [
        ['label' => __('onboarding.progress.account_created'), 'status' => 'complete'],
        ['label' => __('onboarding.progress.email_verified'), 'status' => 'complete'],
        ['label' => __('onboarding.progress.client_account_connected'), 'status' => 'complete'],
        ['label' => __('onboarding.progress.first_shipment'), 'status' => 'future'],
        ['label' => __('onboarding.progress.first_tracking'), 'status' => 'future'],
    ];
@endphp

<ol {{ $attributes->merge(['class' => 'grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-6']) }} aria-label="{{ __('onboarding.progress.label') }}">
    @foreach ($steps as $step)
        @php
            $status = $step['status'] ?? (($step['complete'] ?? false) ? 'complete' : 'future');
            $classes = match ($status) {
                'complete' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                'current' => 'border-amber-300 bg-amber-50 text-amber-950 ring-1 ring-amber-200',
                default => 'border-slate-200 bg-white text-slate-600',
            };
            $icon = match ($status) {
                'complete' => '✓',
                'current' => '●',
                default => '○',
            };
        @endphp
        <li class="rounded-lg border px-3 py-3 {{ $classes }}">
            <span class="flex items-center gap-2 font-semibold">
                <span aria-hidden="true">{{ $icon }}</span>
                <span>{{ $step['label'] }}</span>
            </span>
        </li>
    @endforeach
</ol>
