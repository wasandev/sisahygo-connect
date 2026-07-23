<?php

namespace App\Livewire\Shipments;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Shipment\ShipmentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShipmentShow extends Component
{
    public string $trackingIdentifier;

    public ?array $shipment = null;

    public ?string $pageError = null;

    public bool $notFound = false;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public function mount(ShipmentQueryService $shipments, string $trackingIdentifier): void
    {
        $this->trackingIdentifier = $trackingIdentifier;
        $this->loadShipment($shipments);
    }

    public function refresh(ShipmentQueryService $shipments): void
    {
        $this->loadShipment($shipments);
    }

    public function render(): View
    {
        return view('livewire.shipments.show')->layout('layouts.app', [
            'title' => __('shipments.detail.title'),
        ]);
    }

    private function loadShipment(ShipmentQueryService $service): void
    {
        $this->pageError = null;
        $this->notFound = false;
        $this->unavailable = false;
        $this->unavailableMessage = null;
        $this->resetErrorBag();

        try {
            $this->shipment = $service->detail(auth()->user(), $this->currentClientAccount(), $this->trackingIdentifier);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0] ?? __('shipments.errors.validation'));
            }
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('shipments.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('shipments.errors.authorization');
        } catch (SisahygoNotFoundException) {
            $this->notFound = true;
            $this->shipment = null;
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
        return app(SisahygoApiErrorMessage::class)->message($exception, 'shipments');
    }
}
