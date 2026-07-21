<?php

namespace App\Application\Payment;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContext;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\DTO\ClientPaymentData;
use App\Integrations\Sisahygo\V1\DTO\PaginationMeta;
use App\Integrations\Sisahygo\V1\DTO\PaymentListQuery;
use App\Integrations\Sisahygo\V1\DTO\PaymentSummaryData;
use App\Integrations\Sisahygo\V1\Endpoints\PaymentsEndpoint;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentQueryService
{
    public function __construct(
        private readonly SisahygoIntegrationContextBuilder $contextBuilder,
        private readonly PaymentsEndpoint $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, summary: array<string, mixed>, meta: array<string, mixed>|null, filters: array<string, mixed>}
     */
    public function list(User $user, ClientAccount $clientAccount, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $query = new PaymentListQuery(
            fromDate: $normalized['from_date'] ?? null,
            toDate: $normalized['to_date'] ?? null,
            paymentStatus: $normalized['payment_status'] ?? null,
            paymentType: $normalized['payment_type'] ?? null,
            orderHeaderNo: $normalized['order_header_no'] ?? null,
            clientReferenceNo: $normalized['client_reference_no'] ?? null,
            page: (int) ($normalized['page'] ?? 1),
            perPage: (int) ($normalized['per_page'] ?? 15),
        );
        $result = $this->payments->list($this->context($user, $clientAccount), $query);

        return [
            'items' => array_map(fn (ClientPaymentData $payment): array => $this->paymentToArray($payment), $result->payments),
            'summary' => $this->summaryToArray($result->summary),
            'meta' => $this->metaToArray($result->pagination),
            'filters' => $normalized,
        ];
    }

    /** @return array<string, mixed> */
    public function detail(User $user, ClientAccount $clientAccount, string $paymentIdentifier): array
    {
        Validator::make(['payment_identifier' => $paymentIdentifier], [
            'payment_identifier' => ['required', 'string', 'max:100', 'regex:/^(AR-P-[A-Za-z0-9-]+|BR-[A-Za-z0-9-]+)$/'],
        ], [
            'payment_identifier.regex' => __('payment.validation.payment_identifier'),
        ])->validate();

        return $this->paymentToArray(
            $this->payments->detail($this->context($user, $clientAccount), trim($paymentIdentifier))
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        $data = Validator::make($filters, [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'payment_status' => ['nullable', 'string', Rule::in(PaymentPresenter::SUPPORTED_STATUSES)],
            'payment_type' => ['nullable', 'string', Rule::in(PaymentPresenter::SUPPORTED_TYPES)],
            'order_header_no' => ['nullable', 'string', 'max:100'],
            'client_reference_no' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'date_to.after_or_equal' => __('payment.validation.date_to_after_or_equal'),
            'payment_status.in' => __('payment.validation.payment_status'),
            'payment_type.in' => __('payment.validation.payment_type'),
        ])->validate();

        return array_filter([
            'from_date' => $data['date_from'] ?? null,
            'to_date' => $data['date_to'] ?? null,
            'payment_status' => $data['payment_status'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'order_header_no' => isset($data['order_header_no']) ? trim((string) $data['order_header_no']) : null,
            'client_reference_no' => isset($data['client_reference_no']) ? trim((string) $data['client_reference_no']) : null,
            'page' => isset($data['page']) ? (int) $data['page'] : 1,
            'per_page' => isset($data['per_page']) ? (int) $data['per_page'] : 15,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function context(User $user, ClientAccount $clientAccount): SisahygoIntegrationContext
    {
        return $this->contextBuilder->build($user, $clientAccount, ClientCapability::PaymentView);
    }

    /** @return array<string, mixed> */
    private function paymentToArray(ClientPaymentData $payment): array
    {
        $payerName = $payment->payerRole === 'sender' ? $payment->sender->name : $payment->receiver->name;

        return [
            'payment_identifier' => $payment->paymentIdentifier,
            'source' => $payment->source,
            'payment_type' => $payment->paymentType,
            'payment_type_label' => PaymentPresenter::typeLabel($payment->paymentType),
            'payer_role' => $payment->payerRole,
            'payer_role_label' => PaymentPresenter::payerRoleLabel($payment->payerRole, $payment->paymentType),
            'payer_name' => $payerName,
            'order_header_no' => $payment->orderHeaderNo,
            'order_header_date' => PaymentPresenter::date($payment->orderHeaderDate),
            'client_reference_no' => $payment->clientReferenceNo,
            'billing_date' => PaymentPresenter::date($payment->billingDate),
            'payment_date' => PaymentPresenter::date($payment->paymentDate),
            'payment_status' => $payment->paymentStatus,
            'payment_status_label' => PaymentPresenter::statusLabel($payment->paymentStatus),
            'payment_status_variant' => PaymentPresenter::statusVariant($payment->paymentStatus),
            'total_amount' => $payment->totalAmount,
            'total_amount_display' => PaymentPresenter::money($payment->totalAmount),
            'paid_amount' => $payment->paidAmount,
            'paid_amount_display' => PaymentPresenter::money($payment->paidAmount),
            'outstanding_amount' => $payment->outstandingAmount,
            'outstanding_amount_display' => PaymentPresenter::money($payment->outstandingAmount),
            'discount_amount' => $payment->discountAmount,
            'discount_amount_display' => PaymentPresenter::money($payment->discountAmount),
            'tax_amount' => $payment->taxAmount,
            'tax_amount_display' => PaymentPresenter::money($payment->taxAmount),
            'invoice' => ['number' => $payment->invoice->number, 'date' => PaymentPresenter::date($payment->invoice->date)],
            'receipt' => ['number' => $payment->receipt->number, 'date' => PaymentPresenter::date($payment->receipt->date)],
            'sender' => ['name' => $payment->sender->name, 'code' => $payment->sender->code, 'branch_name' => $payment->sender->branchName],
            'receiver' => ['name' => $payment->receiver->name, 'code' => $payment->receiver->code, 'branch_name' => $payment->receiver->branchName],
            'tracking_reference' => $payment->trackingReference,
        ];
    }

    /** @return array<string, mixed> */
    private function summaryToArray(PaymentSummaryData $summary): array
    {
        return [
            'record_count' => $summary->recordCount,
            'total_amount' => $summary->totalAmount,
            'total_amount_display' => PaymentPresenter::money($summary->totalAmount),
            'paid_record_count' => $summary->paidRecordCount,
            'outstanding_record_count' => $summary->outstandingRecordCount,
        ];
    }

    /** @return array<string, mixed>|null */
    private function metaToArray(?PaginationMeta $meta): ?array
    {
        return $meta ? [
            'current_page' => $meta->currentPage,
            'per_page' => $meta->perPage,
            'total' => $meta->total,
            'last_page' => $meta->lastPage,
        ] : null;
    }
}
