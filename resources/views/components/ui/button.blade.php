@props([
    'variant' => 'primary', // primary, secondary, outline, ghost, danger
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'secondary' => 'bg-brand-orange text-white hover:bg-orange-600 focus:ring-brand-orange/40',
        'outline' => 'bg-white text-brand-blue border border-brand-blue hover:bg-blue-50 focus:ring-brand-blue/30',
        'ghost' => 'bg-transparent text-brand-navy hover:bg-slate-100 focus:ring-slate-300',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-400',
        default => 'bg-brand-blue text-white hover:bg-blue-700 focus:ring-brand-blue/40',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition connect-focus focus:ring-2 disabled:opacity-50 disabled:pointer-events-none {$classes}"]) }}>
    {{ $slot }}
</button>
