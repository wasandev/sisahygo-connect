@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'disabled' => false,
])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-connect-navy-900 shadow-none',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'warning' => 'bg-orange-500 text-white hover:bg-orange-600',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-connect-blue-600 text-white hover:bg-connect-blue-700',
    };

    $sizeClasses = match ($size) {
        'sm' => 'min-h-11 px-3 py-2 text-xs',
        'lg' => 'min-h-12 px-5 py-3 text-base',
        default => 'min-h-11 px-4 py-2.5 text-sm',
    };

    $stateClasses = $disabled ? 'pointer-events-none opacity-60' : '';
    $classes = "connect-focus inline-flex items-center justify-center gap-2 rounded-lg font-semibold shadow-sm transition {$sizeClasses} {$variantClasses} {$stateClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" aria-disabled="{{ $disabled ? 'true' : 'false' }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
