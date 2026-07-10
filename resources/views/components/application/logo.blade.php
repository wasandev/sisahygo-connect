@props([
    'variant' => 'horizontal', // horizontal, vertical, symbol, wordmark
    'mode' => 'light', // light, dark
    'height' => '40',
])

@php
    $src = match ($variant) {
        'vertical' => $mode === 'dark' ? '/images/brand/logo-vertical-on-dark.svg' : '/images/brand/logo-vertical.svg',
        'symbol' => '/images/brand/symbol.svg',
        'wordmark' => '/images/brand/wordmark.svg',
        default => $mode === 'dark' ? '/images/brand/logo-horizontal-on-dark.svg' : '/images/brand/logo-horizontal.svg',
    };
@endphp

<img src="{{ asset($src) }}" alt="Sisahygo Connect" {{ $attributes->merge(['class' => 'select-none']) }} style="height: {{ $height }}px; width: auto;">
