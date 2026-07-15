@props(['label', 'value', 'trend' => null, 'variant' => 'blue'])

@php
    $accent = match ($variant) {
        'success' => 'bg-emerald-500',
        'warning' => 'bg-orange-500',
        'danger' => 'bg-red-500',
        default => 'bg-connect-blue-600',
    };
@endphp

<x-connect.card class="relative overflow-hidden">
    <div class="absolute inset-y-0 left-0 w-1 {{ $accent }}"></div>
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <div class="mt-2 flex items-end justify-between gap-3">
        <p class="text-3xl font-bold text-connect-navy-900">{{ $value }}</p>
        @if ($trend)
            <p class="text-xs font-semibold text-slate-500">{{ $trend }}</p>
        @endif
    </div>
</x-connect.card>
