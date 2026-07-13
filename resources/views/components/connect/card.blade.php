@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title)
        <header class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-base font-semibold text-connect-navy-900">{{ $title }}</h2>
        </header>
    @endif
    <div class="p-6">{{ $slot }}</div>
</section>
