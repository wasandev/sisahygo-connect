<?php

namespace App\Livewire\Workspace;

use App\Application\Search\ResolveUniversalSearch;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class UniversalSearch extends Component
{
    public string $query = '';

    public ?string $message = null;

    public function submit(ResolveUniversalSearch $search): void
    {
        $this->message = null;
        $this->resetErrorBag();

        try {
            $result = $search(auth()->user(), $this->currentClientAccount(), $this->query);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? __('search.validation.required'));
            }

            return;
        } catch (ModelNotFoundException) {
            $this->message = __('search.errors.no_credential');

            return;
        } catch (SisahygoApiException) {
            $this->message = __('search.errors.unavailable');

            return;
        }

        if (! $result['found']) {
            $this->message = __('search.not_found', ['query' => $result['query']]);

            return;
        }

        $this->dispatch('connect-toast', title: __('search.found', ['type' => $result['label']]));
        $this->redirectRoute($result['target_route'], $result['target_parameters'], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.workspace.universal-search');
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
}
