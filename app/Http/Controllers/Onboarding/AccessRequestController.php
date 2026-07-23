<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Onboarding\Models\AccessRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessRequestController extends Controller
{
    public function create(): View
    {
        return view('onboarding.request-access');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'province' => ['required', 'string', 'max:120'],
            'website' => ['nullable', 'url', 'max:255'],
            'number_of_branches' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ], [], __('onboarding.validation.attributes'));

        $accessRequest = AccessRequest::query()->create([
            ...$validated,
            'status' => AccessRequest::STATUS_PENDING,
            'invitation_token' => Str::random(48),
            'submitted_at' => now(),
        ]);

        return redirect()->route('request-access.success')
            ->with('access_request_id', $accessRequest->id);
    }

    public function success(Request $request): View|RedirectResponse
    {
        $accessRequest = AccessRequest::query()->find($request->session()->get('access_request_id'));

        if (! $accessRequest) {
            return redirect()->route('request-access');
        }

        return view('onboarding.request-access-success', [
            'accessRequest' => $accessRequest,
        ]);
    }
}
