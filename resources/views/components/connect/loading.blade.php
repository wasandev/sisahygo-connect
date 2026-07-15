@props(['label' => 'กำลังโหลด'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm']) }}>
    <span class="h-4 w-4 animate-spin rounded-full border-2 border-connect-blue-200 border-t-connect-blue-600"></span>
    <span>{{ $label }}</span>
</div>
