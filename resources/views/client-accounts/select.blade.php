<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-connect-navy-900">{{ __('account_selection.title') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('account_selection.description') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-connect-blue-100 bg-connect-blue-50 px-4 py-3 text-sm text-connect-blue-800">
            {{ session('status') }}
        </div>
    @endif

    @if ($accounts->isEmpty())
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            {{ __('account_selection.no_accounts') }}
        </p>
    @else
        <div class="space-y-3">
            @foreach ($accounts as $account)
                <form method="POST" action="{{ route('client-accounts.select.store') }}">
                    @csrf
                    <input type="hidden" name="client_account_id" value="{{ $account->id }}">
                    <button type="submit" class="connect-focus w-full rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:border-connect-blue-200 hover:bg-connect-blue-50">
                        <span class="block text-sm font-semibold text-connect-navy-900">{{ $account->name }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ __('account_selection.account_code', ['code' => $account->code]) }}</span>
                        <span class="mt-3 inline-flex rounded-md bg-connect-blue-600 px-3 py-1.5 text-xs font-semibold text-white">
                            {{ __('account_selection.select') }}
                        </span>
                    </button>
                </form>
            @endforeach
        </div>
    @endif
</x-guest-layout>
