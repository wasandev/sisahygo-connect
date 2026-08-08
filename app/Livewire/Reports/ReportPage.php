<?php

namespace App\Livewire\Reports;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Application\Reports\ReportDefinitions;
use App\Application\Reports\ReportQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReportPage extends Component
{
    public string $report;
    public array $definition = [];
    public array $rows = [];
    public array $summary = [];
    public ?array $meta = null;
    public ?string $pageError = null;
    public ?string $unavailableMessage = null;
    public bool $canExport = false;
    public ?string $lastRefreshedAt = null;

    #[Url(as: 'date_from', except: null)] public ?string $dateFrom = null;
    #[Url(as: 'date_to', except: null)] public ?string $dateTo = null;
    #[Url(except: 'all')] public string $relationship = 'all';
    #[Url(except: '')] public string $status = '';
    #[Url(except: '')] public string $search = '';
    #[Url(except: '')] public string $type = '';
    #[Url(as: 'client_reference', except: '')] public string $clientReference = '';
    #[Url(as: 'batch_reference', except: '')] public string $batchReference = '';
    #[Url(as: 'pricing_status', except: '')] public string $pricingStatus = '';
    #[Url(as: 'payment_status', except: '')] public string $paymentStatus = '';
    #[Url(as: 'payment_type', except: '')] public string $paymentType = '';
    #[Url(except: 1)] public int $page = 1;
    #[Url(as: 'per_page', except: 25)] public int $perPage = 25;

    public function mount(string $report): void
    {
        abort_unless(isset(ReportDefinitions::all()[$report]), 404);
        $this->report = $report;
        $this->definition = ReportDefinitions::all()[$report];
        $today = now(config('app.timezone'));
        $this->dateFrom ??= $today->copy()->startOfMonth()->toDateString();
        $this->dateTo ??= $today->toDateString();
        $account = $this->currentClientAccount();
        Gate::authorize('report.view', $account);
        $this->canExport = Gate::allows('report.export', $account);
        $this->loadReport();
    }

    public function search(): void { $this->page = 1; $this->loadReport(); }
    public function refresh(): void { $this->loadReport(); }
    public function clearFilters(): void
    {
        $this->dateFrom = $this->dateTo = null; $this->relationship = 'all'; $this->status = $this->search = $this->type = $this->clientReference = $this->batchReference = $this->pricingStatus = $this->paymentStatus = $this->paymentType = ''; $this->page = 1; $this->loadReport();
    }
    public function nextPage(): void { if (($this->meta['last_page'] ?? $this->page) > $this->page) { $this->page++; $this->loadReport(); } }
    public function previousPage(): void { if ($this->page > 1) { $this->page--; $this->loadReport(); } }

    public function exportUrl(): string
    {
        return route('reports.export', array_merge(['report' => $this->report], $this->filters()));
    }

    public function displayValue(array $row, string $column): string
    {
        $value = data_get($row, $column);

        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return __('reports.values.boolean.'.($value ? 'true' : 'false'));
        }

        $translationKey = 'reports.values.'.$column.'.'.$value;
        $translation = __($translationKey);

        return $translation === $translationKey ? (string) $value : (string) $translation;
    }

    public function render(): View
    {
        return view('livewire.reports.page')->layout('layouts.app', ['title' => $this->definition['title'] ?? __('navigation.reports')]);
    }

    private function loadReport(): void
    {
        $this->pageError = null; $this->unavailableMessage = null; $this->resetErrorBag();
        try {
            $result = app(ReportQueryService::class)->fetch(auth()->user(), $this->currentClientAccount(), $this->report, $this->filters());
            $this->rows = $result['data']['rows'] ?? [];
            $this->summary = $result['data']['summary'] ?? [];
            $this->meta = $result['data']['pagination'] ?? null;
            $this->lastRefreshedAt = now(config('app.timezone'))->format('Y-m-d H:i');
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) $this->addError($field, $messages[0] ?? __('reports.errors.validation'));
        } catch (ModelNotFoundException) {
            $this->unavailableMessage = __('reports.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailableMessage = __('reports.errors.authorization');
        } catch (SisahygoApiException $exception) {
            $this->pageError = app(SisahygoApiErrorMessage::class)->message($exception, 'reports');
        }
    }

    private function filters(): array
    {
        return [
            'date_from' => $this->dateFrom, 'date_to' => $this->dateTo, 'relationship' => $this->relationship,
            'status' => $this->status, 'search' => $this->search, 'type' => $this->type,
            'client_reference' => $this->clientReference, 'batch_reference' => $this->batchReference, 'pricing_status' => $this->pricingStatus,
            'payment_status' => $this->paymentStatus, 'payment_type' => $this->paymentType, 'page' => $this->page, 'per_page' => $this->perPage,
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

        if ($clientAccount) {
            app()->instance(ClientAccount::class, $clientAccount);

            return $clientAccount;
        }

        return app(ClientAccount::class);
    }
}
