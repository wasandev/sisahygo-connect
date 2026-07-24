<section class="mx-auto grid w-full max-w-6xl gap-8 px-5 py-8 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-12">
    <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-connect-orange-600">{{ __('onboarding.request_access.eyebrow') }}</p>
        <h1 class="mt-3 text-3xl font-bold leading-tight text-connect-navy-900 sm:text-4xl">{{ __('onboarding.request_access.title') }}</h1>
        <p class="mt-4 text-base leading-7 text-slate-600">{{ __('onboarding.request_access.description') }}</p>
        <div class="mt-6 rounded-lg border border-connect-blue-100 bg-connect-blue-50 p-4 text-sm leading-6 text-connect-blue-900">
            {{ __('onboarding.request_access.notice') }}
        </div>
    </div>

    @if ($state === 'success' && $successResult)
        <x-connect.card :title="__('onboarding.success.title')" :description="__('onboarding.success.description', ['company' => $company_name])">
            <div class="space-y-4">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-900">
                    {{ __('onboarding.success.pending_note') }}
                </div>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="font-semibold text-slate-700">{{ __('onboarding.success.request_no') }}</dt>
                        <dd class="mt-1 break-words text-connect-navy-900">{{ $successResult['request_no'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="font-semibold text-slate-700">{{ __('onboarding.success.submitted_email') }}</dt>
                        <dd class="mt-1 break-words text-connect-navy-900">{{ $submittedEmail }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                        <dt class="font-semibold text-slate-700">{{ __('onboarding.success.status_label') }}</dt>
                        <dd class="mt-1 text-connect-navy-900">{{ $successResult['status_label'] ?? __('onboarding.statuses.pending') }}</dd>
                    </div>
                </dl>
                <x-connect.button :href="route('welcome')" class="w-full sm:w-auto">{{ __('onboarding.success.back_home') }}</x-connect.button>
            </div>
        </x-connect.card>
    @else
        <x-connect.card :title="__('onboarding.request_access.card_title')" :description="__('onboarding.request_access.card_description')">
            <form wire:submit="submit" class="grid gap-4 sm:grid-cols-2">
                @if ($pageError)
                    <div class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ $pageError }}
                    </div>
                @endif
                @error('page')
                    <div class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
                @enderror
                <div class="sm:col-span-2">
                    <label for="company_name" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.company_name') }} *</label>
                    <input id="company_name" wire:model="company_name" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('company_name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="contact_name" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.contact_name') }} *</label>
                    <input id="contact_name" wire:model="contact_name" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('contact_name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.email') }} *</label>
                    <input id="email" type="email" wire:model="email" required autocomplete="email" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('email') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.phone') }} *</label>
                    <input id="phone" wire:model="phone" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('phone') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="province" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.province') }} *</label>
                    <input id="province" wire:model="province" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('province') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="website" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.website') }}</label>
                    <input id="website" type="url" wire:model="website" placeholder="https://example.com" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('website') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="number_of_branches" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.number_of_branches') }}</label>
                    <input id="number_of_branches" type="number" min="1" wire:model="number_of_branches" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    @error('number_of_branches') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="additional_notes" class="text-sm font-semibold text-slate-700">{{ __('onboarding.fields.additional_notes') }}</label>
                    <textarea id="additional_notes" wire:model="additional_notes" rows="4" class="connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm"></textarea>
                    @error('additional_notes') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <x-connect.button type="submit" size="lg" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">{{ __('onboarding.request_access.submit') }}</span>
                        <span wire:loading wire:target="submit">{{ __('onboarding.request_access.submitting') }}</span>
                    </x-connect.button>
                </div>
            </form>
        </x-connect.card>
    @endif
</section>
