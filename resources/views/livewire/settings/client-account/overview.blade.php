<?php

use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Livewire\Volt\Component;

new class extends Component
{
    public ?array $account = null;

    public function mount(CurrentClientAccountResolver $resolver): void
    {
        $clientAccount = $resolver->resolveForUser(auth()->user());

        if (! $clientAccount) {
            return;
        }

        $this->account = [
            'name' => $clientAccount->name,
            'code' => $clientAccount->code,
            'status' => $clientAccount->status->value,
            'users_count' => $clientAccount->memberships()->where('is_active', true)->count(),
            'customers_count' => $clientAccount->customerLinks()->where('is_active', true)->count(),
            'capabilities_count' => $clientAccount->capabilities()->where('is_enabled', true)->count(),
        ];
    }
}; ?>

<x-connect.card :title="__('client_account.title')">
    @if ($account)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.account') }}</p>
                <p class="mt-1 text-lg font-semibold text-connect-navy-900">{{ $account['name'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $account['code'] }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.status') }}</p>
                <p class="mt-1 text-lg font-semibold capitalize text-connect-navy-900">{{ $account['status'] }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.active_users') }}</p>
                <p class="mt-1 text-lg font-semibold text-connect-navy-900">{{ $account['users_count'] }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">{{ __('client_account.linked_customers') }}</p>
                <p class="mt-1 text-lg font-semibold text-connect-navy-900">{{ $account['customers_count'] }}</p>
            </div>
        </div>
    @else
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-connect-blue-600">{{ __('client_account.foundation_ready') }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('client_account.empty_account') }}</p>
        </div>
    @endif
</x-connect.card>