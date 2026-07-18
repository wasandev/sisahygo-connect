<?php

namespace App\Livewire\Dashboard;

use App\Application\Dashboard\GetCustomerDashboard;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Component;

class CustomerDashboard extends Component
{
    public ?array $dashboard = null;

    public ?string $pageError = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public function mount(GetCustomerDashboard $dashboard): void
    {
        $this->loadDashboard($dashboard);
    }

    public function refresh(GetCustomerDashboard $dashboard): void
    {
        $this->loadDashboard($dashboard);
    }

    public function render(): View
    {
        return view('livewire.dashboard.customer-dashboard')->layout('layouts.app', [
            'title' => __('dashboard.title'),
        ]);
    }

    private function loadDashboard(GetCustomerDashboard $service): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;

        try {
            $this->dashboard = $service(auth()->user(), $this->currentClientAccount());
        } catch (ModelNotFoundException) {
            $this->dashboard = null;
            $this->unavailable = true;
            $this->unavailableMessage = __('dashboard.errors.no_credential');
        } catch (AuthorizationException) {
            $this->dashboard = null;
            $this->unavailable = true;
            $this->unavailableMessage = __('dashboard.errors.authorization');
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

        if (! $clientAccount) {
            throw (new ModelNotFoundException)->setModel(ClientAccount::class);
        }

        app()->instance(ClientAccount::class, $clientAccount);

        return $clientAccount;
    }

    private function safeApiMessage(SisahygoApiException $exception): string
    {
        return match (true) {
            $exception instanceof SisahygoAuthenticationException => __('dashboard.errors.authentication'),
            $exception instanceof SisahygoAuthorizationException => __('dashboard.errors.authorization'),
            $exception instanceof SisahygoConnectionException => __('dashboard.errors.connection'),
            $exception instanceof SisahygoValidationException => __('dashboard.errors.validation'),
            $exception instanceof SisahygoRateLimitException => __('dashboard.errors.rate_limited'),
            $exception instanceof SisahygoServerException => __('dashboard.errors.server'),
            $exception instanceof SisahygoUnexpectedResponseException => __('dashboard.errors.malformed'),
            default => __('dashboard.errors.unexpected'),
        };
    }
}
