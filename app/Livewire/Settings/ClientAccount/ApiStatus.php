<?php

namespace App\Livewire\Settings\ClientAccount;

use App\Application\System\CheckSisahygoApiConnectivity;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\On;
use Livewire\Component;

class ApiStatus extends Component
{
    public ?array $status = null;

    public function mount(CheckSisahygoApiConnectivity $connectivity): void
    {
        $this->check($connectivity);
    }

    public function refresh(CheckSisahygoApiConnectivity $connectivity): void
    {
        $this->check($connectivity);
    }

    #[On('sisahygo-credential-updated')]
    public function refreshAfterCredentialUpdate(CheckSisahygoApiConnectivity $connectivity): void
    {
        $this->check($connectivity);
    }

    public function render(): View
    {
        return view('livewire.settings.client-account.api-status');
    }

    private function check(CheckSisahygoApiConnectivity $connectivity): void
    {
        $this->status = $connectivity(auth()->user(), $this->currentClientAccount());
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
}
