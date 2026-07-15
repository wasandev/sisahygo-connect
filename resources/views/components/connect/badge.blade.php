@props(['variant' => 'neutral'])

@php
    $classes = match ($variant) {
        'blue' => 'bg-connect-blue-50 text-connect-blue-700 ring-connect-blue-100',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'warning' => 'bg-orange-50 text-orange-700 ring-orange-100',
        'danger' => 'bg-red-50 text-red-700 ring-red-100',
        default => 'bg-slate-50 text-slate-700 ring-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
