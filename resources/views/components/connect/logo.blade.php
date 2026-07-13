@props([
    'variant' => 'horizontal',
    'class' => 'h-10 w-auto',
])

@php
    $src = match ($variant) {
        'symbol' => asset('images/brand/symbol.svg'),
        'vertical' => asset('images/brand/logo-vertical.svg'),
        'dark' => asset('images/brand/logo-horizontal-on-dark.svg'),
        default => asset('images/brand/logo-horizontal.svg'),
    };
@endphp

<img src="{{ $src }}" alt="Sisahygo Connect" {{ $attributes->merge(['class' => $class]) }}>
