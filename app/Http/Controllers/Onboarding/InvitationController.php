<?php

namespace App\Http\Controllers\Onboarding;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Onboarding\ActivateInvitation;
use App\Http\Controllers\Controller;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token, ActivateInvitation $activation): View
    {
        try {
            return view('onboarding.invitation', [
                'token' => $token,
                'invitation' => $activation->preview($token),
                'errorMessage' => null,
            ]);
        } catch (ValidationException $exception) {
            return $this->failureView($token, $exception->errors()['invitation'][0] ?? __('onboarding.invitation.errors.invalid'));
        } catch (SisahygoConnectionException $exception) {
            report($exception);

            return $this->failureView($token, __('onboarding.invitation.errors.connection'));
        } catch (SisahygoApiException $exception) {
            report($exception);

            return $this->failureView($token, app(SisahygoApiErrorMessage::class)->message($exception, 'onboarding'));
        }
    }

    public function activate(Request $request, string $token, ActivateInvitation $activation): RedirectResponse|View
    {
        try {
            $user = $activation->activate($token, $request->only(['email', 'password', 'password_confirmation']));

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('onboarding.welcome');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput($request->except(['password', 'password_confirmation']));
        } catch (SisahygoConnectionException $exception) {
            report($exception);

            return $this->failureView($token, __('onboarding.invitation.errors.connection'));
        } catch (SisahygoApiException $exception) {
            report($exception);

            return $this->failureView($token, app(SisahygoApiErrorMessage::class)->message($exception, 'onboarding'));
        }
    }

    private function failureView(string $token, string $message): View
    {
        return view('onboarding.invitation', [
            'token' => $token,
            'invitation' => null,
            'errorMessage' => $message,
        ]);
    }
}
