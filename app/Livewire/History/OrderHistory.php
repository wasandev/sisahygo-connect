<?php

namespace App\Livewire\History;

use App\Application\History\ListOrderHistory;
use App\Application\Shipment\ShipmentStatusLabels;
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
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OrderHistory extends Component
{
    public string $datePreset = ListOrderHistory::PRESET_LAST_30_DAYS;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $status = '';

    public string $keyword = '';

    public int $page = 1;

    public int $perPage = 15;

    public array $historyItems = [];

    public ?array $meta = null;

    public array $recentReceivers = [];

    public array $recentProducts = [];

    public ?string $pageError = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public array $statusOptions = [];

    public array $datePresetOptions = [];

    public function mount(ListOrderHistory $history): void
    {
        $this->statusOptions = ShipmentStatusLabels::options();
        $this->datePresetOptions = __('history.presets');
        $this->applyDefaults($history);
        $this->status = (string) request()->query('status', $this->status);
        $this->loadHistory($history);
    }

    public function selectDatePreset(ListOrderHistory $history, string $preset): void
    {
        $this->datePreset = $preset;

        if ($preset !== ListOrderHistory::PRESET_CUSTOM) {
            $dates = $history->datesForPreset($preset);
            $this->dateFrom = $dates['date_from'];
            $this->dateTo = $dates['date_to'];
        }

        $this->page = 1;
        $this->loadHistory($history);
    }

    public function search(ListOrderHistory $history): void
    {
        $this->datePreset = ListOrderHistory::PRESET_CUSTOM;
        $this->page = 1;
        $this->loadHistory($history);
    }

    public function refresh(ListOrderHistory $history): void
    {
        $this->loadHistory($history);
    }

    public function clearFilters(ListOrderHistory $history): void
    {
        $this->status = '';
        $this->keyword = '';
        $this->page = 1;
        $this->applyDefaults($history);
        $this->resetErrorBag();
        $this->loadHistory($history);
    }

    public function nextPage(ListOrderHistory $history): void
    {
        if (($this->meta['last_page'] ?? $this->page) <= $this->page) {
            return;
        }

        $this->page++;
        $this->loadHistory($history);
    }

    public function previousPage(ListOrderHistory $history): void
    {
        if ($this->page <= 1) {
            return;
        }

        $this->page--;
        $this->loadHistory($history);
    }

    public function render(): View
    {
        return view('livewire.history.order-history')->layout('layouts.app', [
            'title' => __('history.title'),
        ]);
    }

    private function applyDefaults(ListOrderHistory $history): void
    {
        $defaults = $history->defaults();
        $this->datePreset = $defaults['preset'];
        $this->dateFrom = $defaults['date_from'];
        $this->dateTo = $defaults['date_to'];
    }

    private function loadHistory(ListOrderHistory $service): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;
        $this->resetErrorBag();

        try {
            $result = $service(auth()->user(), $this->currentClientAccount(), [
                'preset' => $this->datePreset,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'status' => $this->status,
                'keyword' => $this->keyword,
                'page' => $this->page,
                'per_page' => $this->perPage,
            ]);

            $this->historyItems = $result['items'];
            $this->meta = $result['meta'];
            $this->recentReceivers = $result['recent_receivers'];
            $this->recentProducts = $result['recent_products'];
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($this->fieldName($field), $messages[0] ?? __('history.errors.validation'));
            }
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('history.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('history.errors.authorization');
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
            $exception instanceof SisahygoAuthenticationException => __('history.errors.authentication'),
            $exception instanceof SisahygoAuthorizationException => __('history.errors.authorization'),
            $exception instanceof SisahygoConnectionException => __('history.errors.connection'),
            $exception instanceof SisahygoValidationException => __('history.errors.validation'),
            $exception instanceof SisahygoRateLimitException => __('history.errors.rate_limited'),
            $exception instanceof SisahygoServerException => __('history.errors.server'),
            $exception instanceof SisahygoUnexpectedResponseException => __('history.errors.malformed'),
            default => __('history.errors.unexpected'),
        };
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
