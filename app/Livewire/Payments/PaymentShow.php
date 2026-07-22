<?php

namespace App\Livewire\Payments;

use App\Application\Payment\PaymentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PaymentShow extends Component
{
    public string $paymentIdentifier;

    public ?array $payment = null;

    public ?string $pageError = null;

    public bool $notFound = false;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public function mount(PaymentQueryService $payments, string $paymentIdentifier): void
    {
        $this->paymentIdentifier = $paymentIdentifier;
        $this->loadPayment($payments);
    }

    public function refresh(PaymentQueryService $payments): void
    {
        $this->loadPayment($payments);
    }

    public function render(): View
    {
        return view('livewire.payments.show')->layout('layouts.app', [
            'title' => __('payment.detail.title'),
        ]);
    }

    private function loadPayment(PaymentQueryService $service): void
    {
        $this->pageError = null;
        $this->notFound = false;
        $this->unavailable = false;
        $this->unavailableMessage = null;
        $this->resetErrorBag();

        try {
            $this->payment = $service->detail(auth()->user(), $this->currentClientAccount(), $this->paymentIdentifier);
        } catch (ValidationException) {
            $this->notFound = true;
            $this->payment = null;
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('payment.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('payment.errors.authorization');
        } catch (SisahygoNotFoundException) {
            $this->notFound = true;
            $this->payment = null;
        } catch (SisahygoApiException $exception) {
            $this->pageError = $this->safeApiMessage($exception);
        }
    }

    private function currentClientAccount(): ClientAccount
    {
        if (app()->bound(ClientAccount::class)) {
            return app(ClientAccount::class);
        }

        $clientAccount = app(CurrentClientAccountResolver::class)->resolve(
            auth()->user(),
            session()->get(CurrentClientAccountResolver::SESSION_KEY),
        )->clientAccount;

        if ($clientAccount) {
            app()->instance(ClientAccount::class, $clientAccount);

            return $clientAccount;
        }

        return app(ClientAccount::class);
    }

    private function safeApiMessage(SisahygoApiException $exception): string
    {
        return match (true) {
            $exception instanceof SisahygoAuthenticationException => __('payment.errors.authentication'),
            $exception instanceof SisahygoAuthorizationException => __('payment.errors.authorization'),
            $exception instanceof SisahygoConnectionException => __('payment.errors.connection'),
            $exception instanceof SisahygoValidationException => __('payment.errors.validation'),
            $exception instanceof SisahygoRateLimitException => __('payment.errors.rate_limited'),
            $exception instanceof SisahygoServerException => __('payment.errors.server'),
            default => __('payment.errors.unexpected'),
        };
    }
}
