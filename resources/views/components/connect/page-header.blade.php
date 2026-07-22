@props(['title', 'description' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase text-connect-blue-600">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-0.5 text-xl font-bold text-connect-navy-900 sm:text-2xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
