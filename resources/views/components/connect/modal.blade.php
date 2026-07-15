@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-5 shadow-xl']) }} role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <h2 class="text-lg font-semibold text-connect-navy-900">{{ $title }}</h2>
    @if ($description)
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    <div class="mt-5">{{ $slot }}</div>
    @if (isset($actions))
        <div class="mt-5 flex justify-end gap-2">{{ $actions }}</div>
    @endif
</div>
