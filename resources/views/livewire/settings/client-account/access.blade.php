<?php

use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Livewire\Volt\Component;

new class extends Component
{
    public array $customerLinks = [];
    public array $capabilities = [];

    public function mount(CurrentClientAccountResolver $resolver): void
    {
        $clientAccount = $resolver->resolveForUser(auth()->user());

        if (! $clientAccount) {
            return;
        }

        $this->customerLinks = $clientAccount->customerLinks()
            ->where('is_active', true)
            ->orderBy('customer_id')
            ->get()
            ->map(fn ($link) => [
                'customer_id' => $link->customer_id,
                'can_send' => $link->can_send,
                'can_receive' => $link->can_receive,
                'can_view_payment' => $link->can_view_payment,
            ])
            ->all();

        $this->capabilities = $clientAccount->capabilities()
            ->where('is_enabled', true)
            ->orderBy('capability')
            ->pluck('capability')
            ->map(fn ($capability) => $capability->value)
            ->all();
    }
}; ?>

<x-connect.card :title="__('client_account.access_foundation')">
    <div class="space-y-5">
        <div>
            <p class="text-sm font-semibold text-connect-navy-900">{{ __('client_account.customer_links') }}</p>
            @if ($customerLinks)
                <div class="mt-3 space-y-2">
                    @foreach ($customerLinks as $link)
                        <div class="rounded-lg border border-slate-200 p-3 text-sm text-slate-600">
                            <span class="font-semibold text-connect-navy-900">{{ __('client_account.customer_number', ['id' => $link['customer_id']]) }}</span>
                            <span class="ml-2">{{ __('client_account.send') }}: {{ $link['can_send'] ? __('client_account.yes') : __('client_account.no') }}</span>
                            <span class="ml-2">{{ __('client_account.receive') }}: {{ $link['can_receive'] ? __('client_account.yes') : __('client_account.no') }}</span>
                            <span class="ml-2">{{ __('client_account.payment') }}: {{ $link['can_view_payment'] ? __('client_account.yes') : __('client_account.no') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-slate-600">{{ __('client_account.empty_customer_links') }}</p>
            @endif
        </div>

        <div>
            <p class="text-sm font-semibold text-connect-navy-900">{{ __('client_account.capabilities') }}</p>
            @if ($capabilities)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($capabilities as $capability)
                        <span class="rounded-md bg-connect-blue-50 px-2.5 py-1 text-xs font-semibold text-connect-blue-700">{{ $capability }}</span>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-slate-600">{{ __('client_account.empty_capabilities') }}</p>
            @endif
        </div>
    </div>
</x-connect.card>