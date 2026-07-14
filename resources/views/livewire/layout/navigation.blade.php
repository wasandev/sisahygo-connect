<?php

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public string $title = '';

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $menuItems = [
        ['label' => __('navigation.dashboard'), 'route' => 'dashboard'],
        ['label' => __('navigation.order_checking'), 'route' => 'order-checking'],
        ['label' => __('navigation.shipments'), 'route' => 'shipments'],
        ['label' => __('navigation.tracking'), 'route' => 'tracking'],
        ['label' => __('navigation.history'), 'route' => 'history'],
        ['label' => __('navigation.payments'), 'route' => 'payments'],
        ['label' => __('navigation.reports'), 'route' => 'reports'],
        ['label' => __('navigation.settings'), 'route' => 'settings'],
    ];

    $localizedTitle = $title ?: __('navigation.dashboard');
    $currentClientAccount = app()->bound(ClientAccount::class) ? app(ClientAccount::class) : null;
@endphp

<div x-data="{ drawerOpen: false, userMenuOpen: false }" x-on:keydown.escape.window="drawerOpen = false; userMenuOpen = false">
    <aside class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-slate-200 bg-white lg:flex lg:flex-col">
        <div class="flex h-20 items-center border-b border-slate-100 px-6">
            <a href="{{ route('dashboard') }}" wire:navigate class="connect-focus rounded-lg">
                <x-connect.logo class="h-10 w-auto" />
            </a>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6" aria-label="Primary">
            @foreach ($menuItems as $item)
                <x-connect.nav-link
                    :href="route($item['route'])"
                    :active="request()->routeIs($item['route'])"
                    wire:navigate
                >
                    {{ $item['label'] }}
                </x-connect.nav-link>
            @endforeach
        </nav>

        <div class="border-t border-slate-100 p-4">
            <div class="rounded-lg bg-slate-50 p-4">
                @if ($currentClientAccount)
                    <p class="truncate text-xs font-semibold text-connect-blue-700">{{ $currentClientAccount->name }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $currentClientAccount->code }}</p>
                @endif
                <p class="mt-3 text-sm font-semibold text-connect-navy-900">{{ auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </aside>

    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
        <div x-show="drawerOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/40" x-on:click="drawerOpen = false"></div>

        <aside x-show="drawerOpen" x-transition class="fixed inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col bg-white shadow-xl">
            <div class="flex h-20 items-center justify-between border-b border-slate-100 px-5">
                <a href="{{ route('dashboard') }}" wire:navigate x-on:click="drawerOpen = false" class="connect-focus rounded-lg">
                    <x-connect.logo class="h-9 w-auto" />
                </a>

                <button type="button" x-on:click="drawerOpen = false" class="connect-focus rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                    <span class="sr-only">{{ __('navigation.close_navigation') }}</span>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6" aria-label="Mobile primary">
                @foreach ($menuItems as $item)
                    <x-connect.nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])" wire:navigate x-on:click="drawerOpen = false">
                        {{ $item['label'] }}
                    </x-connect.nav-link>
                @endforeach
            </nav>

            <div class="border-t border-slate-100 p-4">
                @if ($currentClientAccount)
                    <p class="truncate text-xs font-semibold text-connect-blue-700">{{ $currentClientAccount->name }}</p>
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $currentClientAccount->code }}</p>
                @endif
                <p class="mt-3 text-sm font-semibold text-connect-navy-900">{{ auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </aside>
    </div>

    <header class="fixed left-0 right-0 top-0 z-30 h-20 border-b border-slate-200 bg-white/95 backdrop-blur lg:left-72">
        <div class="flex h-full items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" x-on:click="drawerOpen = true" class="connect-focus rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-connect-navy-900 lg:hidden">
                    <span class="sr-only">{{ __('navigation.open_navigation') }}</span>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>

                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-connect-blue-600">Sisahygo Connect</p>
                    <h1 class="truncate text-xl font-semibold text-connect-navy-900 sm:text-2xl">{{ $localizedTitle }}</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" class="connect-focus rounded-lg border border-slate-200 bg-white p-2.5 text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-connect-navy-900">
                    <span class="sr-only">{{ __('navigation.notifications') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a3 3 0 0 0 6 0" />
                    </svg>
                </button>

                <div class="relative">
                    <button type="button" x-on:click="userMenuOpen = ! userMenuOpen" class="connect-focus flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2 text-left shadow-sm transition hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-connect-blue-600 text-sm font-semibold text-white">
                            {{ \Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                        </span>
                        <span class="hidden min-w-0 sm:block">
                            <span class="block truncate text-sm font-semibold text-connect-navy-900">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $currentClientAccount?->name ?? __('navigation.account') }}</span>
                        </span>
                        <svg class="hidden h-4 w-4 text-slate-400 sm:block" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="userMenuOpen" x-cloak x-transition x-on:click.outside="userMenuOpen = false" class="absolute right-0 mt-2 w-56 rounded-lg border border-slate-200 bg-white py-2 shadow-lg">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-connect-navy-900">{{ auth()->user()->name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>

                        <a href="{{ route('profile') }}" wire:navigate class="block px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-connect-navy-900">
                            {{ __('navigation.profile') }}
                        </a>
                        <a href="{{ route('settings') }}" wire:navigate class="block px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-connect-navy-900">
                            {{ __('navigation.settings') }}
                        </a>
                        <form method="POST" action="{{ route('client-accounts.change') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-connect-navy-900">
                                {{ __('account_selection.change') }}
                            </button>
                        </form>
                        <button wire:click="logout" class="block w-full px-4 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-connect-navy-900">
                            {{ __('navigation.logout') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>
