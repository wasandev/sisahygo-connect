<?php

namespace App\Livewire\Shipments;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Shipment\ShipmentQueryService;
use App\Application\Shipment\ShipmentStatusLabels;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShipmentIndex extends Component
{
    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $status = '';

    public string $keyword = '';

    public int $page = 1;

    public int $perPage = 15;

    public array $shipments = [];

    public ?array $meta = null;

    public ?string $pageError = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public array $statusOptions = [];

    public function mount(ShipmentQueryService $shipments): void
    {
        $this->statusOptions = ShipmentStatusLabels::options();
        $this->loadShipments($shipments);
    }

    public function search(ShipmentQueryService $shipments): void
    {
        $this->page = 1;
        $this->loadShipments($shipments);
    }

    public function refresh(ShipmentQueryService $shipments): void
    {
        $this->loadShipments($shipments);
    }

    public function clearFilters(ShipmentQueryService $shipments): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->status = '';
        $this->keyword = '';
        $this->page = 1;
        $this->resetErrorBag();
        $this->loadShipments($shipments);
    }

    public function nextPage(ShipmentQueryService $shipments): void
    {
        if (($this->meta['last_page'] ?? $this->page) <= $this->page) {
            return;
        }

        $this->page++;
        $this->loadShipments($shipments);
    }

    public function previousPage(ShipmentQueryService $shipments): void
    {
        if ($this->page <= 1) {
            return;
        }

        $this->page--;
        $this->loadShipments($shipments);
    }

    public function render(): View
    {
        return view('livewire.shipments.index')->layout('layouts.app', [
            'title' => __('shipments.list.title'),
        ]);
    }

    private function loadShipments(ShipmentQueryService $service): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;
        $this->resetErrorBag();

        try {
            $result = $service->list(auth()->user(), $this->currentClientAccount(), [
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'status' => $this->status,
                'keyword' => $this->keyword,
                'page' => $this->page,
                'per_page' => $this->perPage,
            ]);

            $this->shipments = $result['items'];
            $this->meta = $result['meta'];
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($this->fieldName($field), $messages[0] ?? __('shipments.errors.validation'));
            }
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('shipments.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('shipments.errors.authorization');
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

    private function fieldName(string $field): string
    {
        return match ($field) {
            'date_from' => 'dateFrom',
            'date_to' => 'dateTo',
            default => $field,
        };
    }
}
