<x-app-layout :title="$title">
    <div class="space-y-6">
        <x-connect.card>
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-connect-blue-600">{{ __('page.placeholder.coming_soon') }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-connect-navy-900">{{ $title }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
            </div>
        </x-connect.card>
    </div>
</x-app-layout>
