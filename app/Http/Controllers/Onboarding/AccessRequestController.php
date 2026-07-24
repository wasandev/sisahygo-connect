<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessRequestController extends Controller
{
    public function create(): View
    {
        return view('onboarding.request-access');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('request-access');
    }

    public function success(): RedirectResponse
    {
        return redirect()->route('request-access');
    }
}
