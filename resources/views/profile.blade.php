<x-app-layout :title="__('navigation.profile')">
    <div class="space-y-6">
        <x-connect.card :title="__('profile.information')">
            <livewire:profile.update-profile-information-form />
        </x-connect.card>

        <x-connect.card :title="__('profile.update_password')">
            <livewire:profile.update-password-form />
        </x-connect.card>

        <x-connect.card :title="__('profile.delete_account')">
            <livewire:profile.delete-user-form />
        </x-connect.card>
    </div>
</x-app-layout>
