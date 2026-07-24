<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-connect.meta :title="__('onboarding.request_access.meta_title')" />
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <x-connect.environment-banner :show-host="false" />
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('welcome') }}" class="connect-focus rounded-md" aria-label="Sisahygo Connect">
                <x-connect.logo class="h-10 w-auto sm:h-12" />
            </a>
            <nav class="flex items-center gap-2" aria-label="{{ __('onboarding.public_nav.label') }}">
                <a href="{{ route('welcome') }}" class="connect-focus rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">{{ __('onboarding.public_nav.home') }}</a>
                <a href="{{ route('login') }}" class="connect-focus rounded-lg px-3 py-2 text-sm font-semibold text-connect-navy-800 hover:bg-white">{{ __('onboarding.public_nav.login') }}</a>
            </nav>
        </header>

        <livewire:onboarding.request-access />
    </main>
    @livewireScripts
</body>
</html>
