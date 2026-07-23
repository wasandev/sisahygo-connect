@php
    $appEnvironment = (string) config('app.env');
    $apiEnvironment = (string) config('sisahygo.api.environment');
    $baseUrl = (string) (config('sisahygo.api.base_url') ?: config("sisahygo.api.environments.{$apiEnvironment}.base_url"));
    $apiHost = parse_url($baseUrl, PHP_URL_HOST) ?: 'unconfigured';
    $release = collect([
        config('sisahygo.release.version'),
        config('sisahygo.release.build'),
        config('sisahygo.release.commit'),
    ])->map(fn ($value) => substr((string) preg_replace('/[^A-Za-z0-9._-]/', '', trim((string) $value)), 0, 24))
        ->first(fn ($value) => $value !== '');
@endphp

@if ($appEnvironment !== 'production')
    <div class="border-b border-amber-200 bg-amber-50 text-amber-950" role="status" aria-label="Non-production environment">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2 text-xs font-semibold sm:px-6 lg:px-8">
            <span class="rounded bg-amber-200 px-2 py-0.5 uppercase">{{ strtoupper($appEnvironment) }}</span>
            <span>{{ $apiEnvironment === 'sandbox' ? 'Sandbox API' : 'Non-production API' }}</span>
            <span class="text-amber-800">Release Candidate</span>
            <span class="truncate text-amber-700">{{ $apiHost }}</span>
            @if ($release)
                <span class="text-amber-700">Build {{ $release }}</span>
            @endif
        </div>
    </div>
@endif
