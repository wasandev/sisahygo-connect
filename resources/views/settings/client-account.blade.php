<x-app-layout :title="__('navigation.settings')">
    <div class="space-y-6">
        <livewire:settings.client-account.overview />
        <div class="grid gap-6 xl:grid-cols-2">
            <livewire:settings.client-account.members />
            <livewire:settings.client-account.access />
        </div>
    </div>
</x-app-layout>