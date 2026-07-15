@props(['label' => null, 'name' => null, 'hint' => null])

<label class="block">
    @if ($label)
        <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
    @endif
    <textarea name="{{ $name }}" {{ $attributes->merge(['class' => 'connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500']) }}>{{ $slot }}</textarea>
    @if ($hint)
        <span class="mt-1 block text-xs text-slate-500">{{ $hint }}</span>
    @endif
</label>
