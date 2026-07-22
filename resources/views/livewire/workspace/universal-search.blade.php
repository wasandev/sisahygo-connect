<form wire:submit="submit" class="space-y-2">
    <div class="flex min-w-0 gap-2">
        <label for="workspace-universal-search" class="sr-only">{{ __('search.label') }}</label>
        <input id="workspace-universal-search" type="search" wire:model.blur="query" class="connect-focus min-h-11 min-w-0 flex-1 rounded-lg border-slate-300 text-sm shadow-sm placeholder:text-slate-400" placeholder="{{ __('search.placeholder') }}" autocomplete="off">
        <x-connect.button type="submit" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">{{ __('search.submit') }}</span>
            <span wire:loading wire:target="submit">{{ __('search.submitting') }}</span>
        </x-connect.button>
    </div>
    @error('query') <p class="text-sm font-medium text-red-600">{{ $message }}</p> @enderror
    @if ($message)
        <p class="text-sm font-medium text-amber-700">{{ $message }}</p>
    @endif
</form>
