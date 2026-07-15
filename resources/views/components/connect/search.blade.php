@props(['placeholder' => 'ค้นหา'])

<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400" aria-hidden="true">⌕</span>
    <input type="search" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'connect-focus block min-h-11 w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm shadow-sm placeholder:text-slate-400 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500']) }}>
</div>
