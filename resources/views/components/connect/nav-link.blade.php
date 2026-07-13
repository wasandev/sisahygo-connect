@props([
    'active' => false,
])

@php
    $classes = $active
        ? 'bg-connect-blue-50 text-connect-blue-700 ring-1 ring-inset ring-connect-blue-100'
        : 'text-slate-600 hover:bg-slate-50 hover:text-connect-navy-900';
@endphp

<a {{ $attributes->merge([
    'class' => "connect-focus flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition {$classes}",
]) }}>
    <span class="truncate">{{ $slot }}</span>
</a>