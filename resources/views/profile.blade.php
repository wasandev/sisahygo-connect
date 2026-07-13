<x-app-layout title="Profile">
    <div class="space-y-6">
        <x-connect.card title="Profile information">
            <livewire:profile.update-profile-information-form />
        </x-connect.card>

        <x-connect.card title="Update password">
            <livewire:profile.update-password-form />
        </x-connect.card>

        <x-connect.card title="Delete account">
            <livewire:profile.delete-user-form />
        </x-connect.card>
    </div>
</x-app-layout>