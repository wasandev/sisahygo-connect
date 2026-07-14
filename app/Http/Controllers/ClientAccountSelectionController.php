<?php

namespace App\Http\Controllers;

use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientAccountSelectionController extends Controller
{
    public function index(Request $request, CurrentClientAccountResolver $resolver): View|RedirectResponse
    {
        $accounts = $resolver->activeAccountsForUser($request->user());

        if ($accounts->count() === 1) {
            $account = $accounts->first();
            $request->session()->put(CurrentClientAccountResolver::SESSION_KEY, $account->id);

            return redirect()->route('dashboard');
        }

        return view('client-accounts.select', [
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request, CurrentClientAccountResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'client_account_id' => ['required', 'integer'],
        ]);

        $account = $resolver->findAuthorizedForUser($request->user(), (int) $validated['client_account_id']);

        abort_unless($account, 403);

        $request->session()->put(CurrentClientAccountResolver::SESSION_KEY, $account->id);

        return redirect()->route('dashboard')
            ->with('status', __('account_selection.selected'));
    }

    public function change(Request $request): RedirectResponse
    {
        $request->session()->forget(CurrentClientAccountResolver::SESSION_KEY);

        return redirect()->route('client-accounts.select');
    }
}
