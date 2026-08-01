<?php

namespace App\Livewire\Dashboard;

use App\Application\Dashboard\GetCustomerDashboard;
use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Settings\ClientAccountSetupState;
use App\Application\Settings\ResolveClientAccountSetupState;
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

    /** @var array<string, mixed>|null */
    public ?array $setupState = null;

    public function mount(GetCustomerDashboard $dashboard, ResolveClientAccountSetupState $setupState): void
    {
        $clientAccount = $this->currentClientAccount();
        $this->loadDashboard($dashboard, $clientAccount);
        $this->setupState = $this->serializeSetupState($setupState(auth()->user(), $clientAccount));
    }

    public function refresh(GetCustomerDashboard $dashboard, ResolveClientAccountSetupState $setupState): void
    {
        $clientAccount = $this->currentClientAccount();
        $this->loadDashboard($dashboard, $clientAccount, forcePaymentRefresh: true);
        $this->setupState = $this->serializeSetupState($setupState(auth()->user(), $clientAccount));
    }

    public function render(): View
    {
        return view('livewire.dashboard.customer-dashboard', [
            'setupState' => $this->setupState,
        ])->layout('layouts.app', [
            'title' => __('dashboard.title'),
        ]);
    }

    private function loadDashboard(GetCustomerDashboard $service, ?ClientAccount $clientAccount = null, bool $forcePaymentRefresh = false): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;

        try {
            $this->dashboard = $service(auth()->user(), $clientAccount ?? $this->currentClientAccount(), $forcePaymentRefresh);
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

    /** @return array<string, mixed> */
    private function serializeSetupState(ClientAccountSetupState $state): array
    {
        return [
            'steps' => $state->steps,
            'is_ready' => $state->isReady(),
            'completed_steps' => $state->completedSteps(),
            'total_steps' => $state->totalSteps(),
            'can_manage_settings' => $state->canManageSettings,
            'client_account_name' => $state->clientAccountName,
            'next_action_key' => $state->nextActionKey,
        ];
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
