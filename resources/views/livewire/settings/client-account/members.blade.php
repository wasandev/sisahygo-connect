<?php

use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Livewire\Volt\Component;

new class extends Component
{
    public array $members = [];

    public function mount(CurrentClientAccountResolver $resolver): void
    {
        $clientAccount = $resolver->resolveForUser(auth()->user());

        if (! $clientAccount) {
            return;
        }

        $this->members = $clientAccount->memberships()
            ->with('user')
            ->where('is_active', true)
            ->orderBy('role')
            ->get()
            ->map(fn ($membership) => [
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role->value,
            ])
            ->all();
    }
}; ?>

<x-connect.card :title="__('client_account.users')">
    @if ($members)
        <div class="space-y-3">
            @foreach ($members as $member)
                <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 p-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-connect-navy-900">{{ $member['name'] }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $member['email'] }}</p>
                    </div>
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold capitalize text-slate-700">{{ $member['role'] }}</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm leading-6 text-slate-600">{{ __('client_account.empty_users') }}</p>
    @endif
</x-connect.card>