@props(['title' => null, 'description' => null, 'padding' => 'default'])

@php
    $paddingClasses = $padding === 'none' ? '' : 'p-5 sm:p-6';
@endphp

<section {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || $description)
        <header class="border-b border-slate-100 px-5 py-4 sm:px-6">
            @if ($title)
                <h2 class="text-base font-semibold text-connect-navy-900">{{ $title }}</h2>
            @endif
            @if ($description)
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ $description }}</p>
            @endif
        </header>
    @endif
    <div @class([$paddingClasses])>{{ $slot }}</div>
</section>
