@props([
    'steps' => [
        ['label' => 'Account Created', 'complete' => true],
        ['label' => 'Email Verified', 'complete' => true],
        ['label' => 'Client Account Connected', 'complete' => true],
        ['label' => 'First Shipment', 'complete' => false],
        ['label' => 'First Tracking', 'complete' => false],
    ],
])

<ol {{ $attributes->merge(['class' => 'grid gap-3 text-sm sm:grid-cols-5']) }} aria-label="Onboarding progress">
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
