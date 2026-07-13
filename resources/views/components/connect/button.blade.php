@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-connect-blue-600 text-white hover:bg-connect-blue-700',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge([
    'class' => "connect-focus inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition {$classes}",
]) }}>
    {{ $slot }}
</button>
