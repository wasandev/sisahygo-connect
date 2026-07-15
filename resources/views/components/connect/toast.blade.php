@props(['variant' => 'success', 'title', 'message' => null])

@php
    $classes = match ($variant) {
        'warning' => 'border-orange-200 bg-orange-50 text-orange-900',
        'danger' => 'border-red-200 bg-red-50 text-red-900',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-900',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 shadow-sm {$classes}"]) }} role="status">
    <p class="text-sm font-semibold">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm opacity-80">{{ $message }}</p>
    @endif
</div>
