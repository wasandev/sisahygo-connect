<?php

namespace App\Livewire\Dashboard;

use App\Application\Dashboard\GetCustomerDashboard;
use App\Application\Integration\SisahygoApiErrorMessage;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
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
        $this->loadDashboard($dashboard, forcePaymentRefresh: true);
    }

    public function render(): View
    {
        return view('livewire.dashboard.customer-dashboard')->layout('layouts.app', [
            'title' => __('dashboard.title'),
        ]);
    }

    private function loadDashboard(GetCustomerDashboard $service, bool $forcePaymentRefresh = false): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;

        try {
            $this->dashboard = $service(auth()->user(), $this->currentClientAccount(), $forcePaymentRefresh);
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
        return app(SisahygoApiErrorMessage::class)->message($exception, 'dashboard');
    }
}
