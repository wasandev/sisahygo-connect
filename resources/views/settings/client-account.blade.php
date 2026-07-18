<x-app-layout :title="__('navigation.settings')">
    <div class="space-y-4">
        <x-connect.page-header :title="__('navigation.settings')" :description="__('client_account.title')" />

        <livewire:settings.client-account.overview />
        <div class="grid gap-4 xl:grid-cols-2">
            <livewire:settings.client-account.members />
            <livewire:settings.client-account.access />
        </div>
    </div>
</x-app-layout>
