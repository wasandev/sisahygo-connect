@props(['items' => []])

<ol {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($items as $item)
        <li class="flex gap-3">
            <div class="flex flex-col items-center">
                <span @class([
                    'mt-1 h-3 w-3 rounded-full ring-4',
                    'bg-emerald-500 ring-emerald-50' => ($item['state'] ?? 'done') === 'done',
                    'bg-connect-blue-600 ring-connect-blue-50' => ($item['state'] ?? null) === 'current',
                    'bg-slate-300 ring-slate-50' => ($item['state'] ?? null) === 'pending',
                ])></span>
                @unless ($loop->last)
                    <span class="mt-2 h-full min-h-8 w-px bg-slate-200"></span>
                @endunless
            </div>
            <div class="min-w-0 pb-2">
                <p class="text-sm font-semibold text-connect-navy-900">{{ $item['title'] }}</p>
                @if (! empty($item['meta']))
                    <p class="mt-0.5 text-xs text-slate-500">{{ $item['meta'] }}</p>
                @endif
                @if (! empty($item['description']))
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $item['description'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
