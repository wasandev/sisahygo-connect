<?php

namespace App\Livewire\Settings\ClientAccount;

use App\Application\Settings\ResolveClientAccountSetupState;
use App\Application\Settings\SisahygoApiCredentialSetup;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CredentialSetup extends Component
{
    use AuthorizesRequests;

    public string $apiKey = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function save(SisahygoApiCredentialSetup $setup): void
    {
        $account = $this->currentClientAccount();
        $this->authorize('manageSettings', $account);

        $validated = $this->validate([
            'apiKey' => ['required', 'string', 'min:16', 'max:255'],
        ], [], [
            'apiKey' => __('client_account.credential_setup.fields.api_key'),
        ]);

        $apiKey = trim($validated['apiKey']);
        $this->reset('apiKey', 'successMessage', 'errorMessage');

        $result = $setup->verify($apiKey);

        if (! $result->verified) {
            $this->errorMessage = __('client_account.credential_setup.errors.'.($result->reason ?? 'unexpected'));

            return;
        }

        $credential = $setup->storeVerified($account, $apiKey, auth()->user());
        $apiKey = '';
        unset($apiKey);

        $this->successMessage = __('client_account.credential_setup.success', [
            'fingerprint' => $this->shortFingerprint($credential->key_fingerprint),
        ]);

        $this->dispatch('sisahygo-credential-updated');
    }

    public function render(): View
    {
        $setup = app(SisahygoApiCredentialSetup::class);
        $account = $this->currentClientAccount();
        $canManage = auth()->user()?->can('manageSettings', $account) ?? false;
        $credential = $setup->activeCredential($account);

        return view('livewire.settings.client-account.credential-setup', [
            'canManage' => $canManage,
            'credential' => $credential,
            'setupState' => app(ResolveClientAccountSetupState::class)(auth()->user(), $account),
            'fingerprint' => $credential ? $this->shortFingerprint($credential->key_fingerprint) : null,
            'environment' => $credential?->environment->value ?? config('sisahygo.api.environment'),
        ]);
    }

    private function currentClientAccount(): ClientAccount
    {
        if (app()->bound(ClientAccount::class)) {
            return app(ClientAccount::class);
        }

        $clientAccount = app(CurrentClientAccountResolver::class)->resolveForUser(auth()->user());

        if (! $clientAccount) {
            throw (new ModelNotFoundException)->setModel(ClientAccount::class);
        }

        app()->instance(ClientAccount::class, $clientAccount);

        return $clientAccount;
    }

    private function shortFingerprint(string $fingerprint): string
    {
        return substr($fingerprint, 0, 8).'...'.substr($fingerprint, -8);
    }
}
