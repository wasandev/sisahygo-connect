<?php

namespace App\Livewire\Payments;

use App\Application\Payment\PaymentPresenter;
use App\Application\Payment\PaymentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthenticationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoAuthorizationException;
use App\Integrations\Sisahygo\Exceptions\SisahygoConnectionException;
use App\Integrations\Sisahygo\Exceptions\SisahygoNotFoundException;
use App\Integrations\Sisahygo\Exceptions\SisahygoRateLimitException;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Integrations\Sisahygo\Exceptions\SisahygoValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class PaymentIndex extends Component
{
    #[Url(as: 'from_date', except: null)]
    public ?string $dateFrom = null;

    #[Url(as: 'to_date', except: null)]
    public ?string $dateTo = null;

    #[Url(as: 'payment_type', except: '')]
    public string $paymentType = '';

    #[Url(as: 'payment_status', except: '')]
    public string $paymentStatus = '';

    #[Url(as: 'order_header_no', except: '')]
    public string $orderHeaderNo = '';

    #[Url(as: 'client_reference_no', except: '')]
    public string $clientReferenceNo = '';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'per_page', except: 20)]
    public int $perPage = 20;

    public array $payments = [];

    public array $summary = [];

    public ?array $meta = null;

    public ?string $pageError = null;

    public bool $unavailable = false;

    public ?string $unavailableMessage = null;

    public ?string $lastRefreshedAt = null;

    public array $typeOptions = [];

    public array $statusOptions = [];

    public array $perPageOptions = [10, 20, 50];

    public function mount(PaymentQueryService $payments): void
    {
        $this->typeOptions = collect(PaymentPresenter::SUPPORTED_TYPES)
            ->mapWithKeys(fn (string $type) => [$type => PaymentPresenter::typeLabel($type)])
            ->all();
        $this->statusOptions = collect(PaymentPresenter::SUPPORTED_STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => PaymentPresenter::statusLabel($status)])
            ->all();
        $this->loadPayments($payments);
    }

    public function search(PaymentQueryService $payments): void
    {
        $this->page = 1;
        $this->loadPayments($payments);
    }

    public function refresh(PaymentQueryService $payments): void
    {
        $this->loadPayments($payments);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'paymentType', 'paymentStatus', 'perPage'], true)) {
            $this->page = 1;
        }
    }

    public function clearFilter(PaymentQueryService $payments, string $filter): void
    {
        match ($filter) {
            'date' => [$this->dateFrom, $this->dateTo] = [null, null],
            'paymentType' => $this->paymentType = '',
            'paymentStatus' => $this->paymentStatus = '',
            'orderHeaderNo' => $this->orderHeaderNo = '',
            'clientReferenceNo' => $this->clientReferenceNo = '',
            default => null,
        };

        $this->page = 1;
        $this->loadPayments($payments);
    }

    public function clearFilters(PaymentQueryService $payments): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->paymentType = '';
        $this->paymentStatus = '';
        $this->orderHeaderNo = '';
        $this->clientReferenceNo = '';
        $this->page = 1;
        $this->resetErrorBag();
        $this->loadPayments($payments);
    }

    public function nextPage(PaymentQueryService $payments): void
    {
        if (($this->meta['last_page'] ?? $this->page) <= $this->page) {
            return;
        }

        $this->page++;
        $this->loadPayments($payments);
    }

    public function previousPage(PaymentQueryService $payments): void
    {
        if ($this->page <= 1) {
            return;
        }

        $this->page--;
        $this->loadPayments($payments);
    }

    public function hasActiveFilters(): bool
    {
        return $this->dateFrom || $this->dateTo || $this->paymentType !== '' || $this->paymentStatus !== '' || $this->orderHeaderNo !== '' || $this->clientReferenceNo !== '';
    }

    public function activeFilterChips(): array
    {
        $chips = [];

        if ($this->paymentType !== '') {
            $chips[] = ['key' => 'paymentType', 'label' => __('payment.filters.chip_type', ['value' => PaymentPresenter::typeLabel($this->paymentType)])];
        }

        if ($this->paymentStatus !== '') {
            $chips[] = ['key' => 'paymentStatus', 'label' => __('payment.filters.chip_status', ['value' => PaymentPresenter::statusLabel($this->paymentStatus)])];
        }

        if ($this->dateFrom || $this->dateTo) {
            $chips[] = ['key' => 'date', 'label' => __('payment.filters.chip_date', ['value' => ($this->dateFrom ?: __('payment.fallback.empty')).'–'.($this->dateTo ?: __('payment.fallback.empty'))])];
        }

        if ($this->orderHeaderNo !== '') {
            $chips[] = ['key' => 'orderHeaderNo', 'label' => __('payment.filters.chip_order', ['value' => $this->orderHeaderNo])];
        }

        if ($this->clientReferenceNo !== '') {
            $chips[] = ['key' => 'clientReferenceNo', 'label' => __('payment.filters.chip_reference', ['value' => $this->clientReferenceNo])];
        }

        return $chips;
    }

    public function render(): View
    {
        return view('livewire.payments.index')->layout('layouts.app', [
            'title' => __('payment.center.title'),
        ]);
    }

    private function loadPayments(PaymentQueryService $service): void
    {
        $this->pageError = null;
        $this->unavailable = false;
        $this->unavailableMessage = null;
        $this->resetErrorBag();

        try {
            $result = $service->list(auth()->user(), $this->currentClientAccount(), [
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'payment_type' => $this->paymentType,
                'payment_status' => $this->paymentStatus,
                'order_header_no' => $this->orderHeaderNo,
                'client_reference_no' => $this->clientReferenceNo,
                'page' => $this->page,
                'per_page' => $this->perPage,
            ]);

            $this->payments = $result['items'];
            $this->summary = $result['summary'];
            $this->meta = $result['meta'];
            $this->lastRefreshedAt = now(config('app.timezone'))->format('Y-m-d H:i');
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($this->fieldName($field), $messages[0] ?? __('payment.errors.validation'));
            }
        } catch (ModelNotFoundException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('payment.errors.no_credential');
        } catch (AuthorizationException) {
            $this->unavailable = true;
            $this->unavailableMessage = __('payment.errors.authorization');
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
        return match (true) {
            $exception instanceof SisahygoAuthenticationException => __('payment.errors.authentication'),
            $exception instanceof SisahygoAuthorizationException => __('payment.errors.authorization'),
            $exception instanceof SisahygoConnectionException => __('payment.errors.connection'),
            $exception instanceof SisahygoNotFoundException => __('payment.errors.not_found'),
            $exception instanceof SisahygoValidationException => __('payment.errors.validation'),
            $exception instanceof SisahygoRateLimitException => __('payment.errors.rate_limited'),
            $exception instanceof SisahygoServerException => __('payment.errors.server'),
            default => __('payment.errors.unexpected'),
        };
    }

    private function fieldName(string $field): string
    {
        return match ($field) {
            'date_from' => 'dateFrom',
            'date_to' => 'dateTo',
            'payment_type' => 'paymentType',
            'payment_status' => 'paymentStatus',
            'order_header_no' => 'orderHeaderNo',
            'client_reference_no' => 'clientReferenceNo',
            default => $field,
        };
    }
}
