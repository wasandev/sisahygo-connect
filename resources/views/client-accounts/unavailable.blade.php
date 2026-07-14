<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-semibold text-connect-navy-900">{{ __('account_selection.unavailable_title') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('account_selection.unavailable_description') }}</p>
    </div>

    <div class="mt-6 flex justify-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-connect.button type="submit" variant="secondary">
                {{ __('navigation.logout') }}
            </x-connect.button>
        </form>
    </div>
</x-guest-layout>
