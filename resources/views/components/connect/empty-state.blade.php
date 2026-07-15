@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-connect-blue-50 text-connect-blue-700">✓</div>
    <h3 class="mt-4 text-base font-semibold text-connect-navy-900">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500">{{ $description }}</p>
    @endif
    @if (isset($actions))
        <div class="mt-5 flex justify-center gap-2">{{ $actions }}</div>
    @endif
</div>
