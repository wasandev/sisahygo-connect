@props(['title', 'description' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-bold uppercase tracking-wide text-connect-blue-600">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-bold text-connect-navy-900 sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
