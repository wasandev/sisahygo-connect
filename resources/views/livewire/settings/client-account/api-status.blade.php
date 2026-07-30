@php
    $statusValue = $status['status'] ?? 'unknown_error';
    $variant = match ($statusValue) {
        'connected' => 'success',
        'configuration_missing', 'credential_missing', 'rate_limited' => 'warning',
        default => 'danger',
    };
@endphp

<x-connect.card :title="__('client_account.api_status.title')" :description="__('client_account.api_status.description')">
    @if ($status)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.api_status.fields.status') }}</p>
                <p class="mt-1"><x-connect.badge :variant="$variant">{{ __('client_account.api_status.statuses.'.$statusValue) }}</x-connect.badge></p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.api_status.fields.duration') }}</p>
                <p class="mt-1 font-semibold text-connect-navy-900">{{ $status['duration_ms'] }} ms</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.api_status.fields.checked_at') }}</p>
                <p class="mt-1 font-semibold text-connect-navy-900">{{ $status['checked_at'] }}</p>
            </div>
        </div>

        @if ($status['message'])
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">{{ $status['message'] }}</div>
        @endif

        @if (! $canManage)
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" role="status">{{ __('client_account.api_status.admin_guidance') }}</div>
        @endif
    @else
        <x-connect.loading :label="__('client_account.api_status.loading')" />
    @endif

    <div class="mt-4">
        <x-connect.button size="sm" variant="secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
            <span wire:loading.remove wire:target="refresh">{{ __('client_account.api_status.actions.refresh') }}</span>
            <span wire:loading wire:target="refresh">{{ __('client_account.api_status.actions.refreshing') }}</span>
        </x-connect.button>
    </div>
</x-connect.card>
