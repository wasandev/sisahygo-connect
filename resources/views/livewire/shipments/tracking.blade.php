<div class="space-y-6">
    <x-connect.page-header :title="__('shipments.tracking.title')" :description="__('shipments.tracking.description')" :eyebrow="__('shipments.eyebrow')">
        <x-slot:actions>
            <x-connect.button :href="route('shipments')" variant="secondary" wire:navigate>{{ __('shipments.actions.back_to_list') }}</x-connect.button>
        </x-slot:actions>
    </x-connect.page-header>

    <x-connect.card :title="__('shipments.tracking.card_title')" :description="__('shipments.tracking.card_description')">
        <form wire:submit="submit" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
            <div>
                <label for="tracking-identifier" class="text-sm font-semibold text-slate-700">{{ __('shipments.tracking.identifier_label') }}</label>
                <input id="tracking-identifier" wire:model.blur="trackingIdentifier" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('shipments.tracking.identifier_placeholder') }}" autocomplete="off">
                @error('trackingIdentifier') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            <x-connect.button type="submit" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">{{ __('shipments.tracking.submit') }}</span>
                <span wire:loading wire:target="submit">{{ __('shipments.tracking.submitting') }}</span>
            </x-connect.button>
        </form>
    </x-connect.card>

    <x-connect.empty-state :title="__('shipments.tracking.guide_title')" :description="__('shipments.tracking.guide_description')">
        <x-slot:actions>
            <x-connect.button size="sm" :href="route('order-checking')" wire:navigate>{{ __('shipments.actions.create_first') }}</x-connect.button>
            <x-connect.button size="sm" variant="secondary" :href="route('history')" wire:navigate>{{ __('shipments.actions.open_history') }}</x-connect.button>
        </x-slot:actions>
    </x-connect.empty-state>
</div>
