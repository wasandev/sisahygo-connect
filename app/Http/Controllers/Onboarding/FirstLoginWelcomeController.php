<?php

namespace App\Http\Controllers\Onboarding;

use App\Application\Settings\ResolveClientAccountSetupState;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirstLoginWelcomeController extends Controller
{
    public function show(Request $request, CurrentClientAccountResolver $accounts, ResolveClientAccountSetupState $setupState): View|RedirectResponse
    {
        if ($request->user()->onboarding_welcomed_at) {
            return redirect()->route('client-accounts.select');
        }

        $clientAccount = $accounts->resolveForUser($request->user());

        if ($clientAccount) {
            app()->instance(ClientAccount::class, $clientAccount);
        }

        return view('onboarding.first-login-welcome', [
            'clientAccount' => $clientAccount,
            'setupState' => $clientAccount ? $setupState($request->user(), $clientAccount) : null,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'onboarding_welcomed_at' => now(),
        ])->save();

        return redirect()->route('client-accounts.select');
    }
}
