@props(['current' => 1, 'total' => 1])

<nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 text-sm']) }} aria-label="Pagination">
    <button type="button" class="connect-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">ก่อนหน้า</button>
    <span class="text-slate-500">หน้า {{ $current }} จาก {{ $total }}</span>
    <button type="button" class="connect-focus min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">ถัดไป</button>
</nav>
