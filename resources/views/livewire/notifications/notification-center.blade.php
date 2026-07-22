<div class="space-y-6">
    <x-connect.page-header :title="__('notifications.title')" :description="__('notifications.description')" :eyebrow="__('notifications.eyebrow')" />

    <x-connect.toast variant="warning" :title="__('notifications.phase_one.title')" :message="__('notifications.phase_one.message')" />

    <x-connect.card :title="__('notifications.center_title')" padding="none">
        <div class="flex flex-wrap gap-2 border-b border-slate-100 p-4" role="group" aria-label="{{ __('notifications.filters.label') }}">
            <button type="button" wire:click="$set('filter', 'all')" @class([
                'connect-focus min-h-11 rounded-lg border px-3 py-2 text-sm font-semibold',
                'border-connect-blue-600 bg-connect-blue-50 text-connect-blue-700' => $filter === 'all',
                'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $filter !== 'all',
            ])>{{ __('notifications.filters.all') }}</button>
            <button type="button" wire:click="$set('filter', 'unread')" @class([
                'connect-focus min-h-11 rounded-lg border px-3 py-2 text-sm font-semibold',
                'border-connect-blue-600 bg-connect-blue-50 text-connect-blue-700' => $filter === 'unread',
                'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $filter !== 'unread',
            ])>{{ __('notifications.filters.unread') }}</button>
        </div>

        @if ($notifications === [])
            <div class="p-6"><x-connect.empty-state :title="__('notifications.empty.title')" :description="__('notifications.empty.description')" /></div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <article wire:key="notification-{{ $notification['id'] }}" class="p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="break-words text-sm font-semibold text-connect-navy-900">{{ $notification['title'] }}</h3>
                                    @unless ($notification['read'])
                                        <x-connect.badge variant="blue">{{ __('notifications.unread') }}</x-connect.badge>
                                    @endunless
                                </div>
                                <p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ $notification['message'] }}</p>
                            </div>
                            <time class="shrink-0 text-xs font-medium text-slate-500">{{ $notification['time'] }}</time>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </x-connect.card>
</div>
