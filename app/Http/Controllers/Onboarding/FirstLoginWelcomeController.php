<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirstLoginWelcomeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->onboarding_welcomed_at) {
            return redirect()->route('client-accounts.select');
        }

        return view('onboarding.first-login-welcome');
    }

    public function start(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'onboarding_welcomed_at' => now(),
        ])->save();

        return redirect()->route('client-accounts.select');
    }
}
