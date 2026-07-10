@props(['label' => null, 'name' => null, 'error' => null])

<label class="block">
    @if($label)
        <span class="mb-1.5 block text-sm font-semibold text-brand-navy">{{ $label }}</span>
    @endif
    <input name="{{ $name }}" {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm text-brand-text shadow-sm connect-focus']) }}>
    @if($error)
        <span class="mt-1 block text-sm text-red-600">{{ $error }}</span>
    @endif
</label>
