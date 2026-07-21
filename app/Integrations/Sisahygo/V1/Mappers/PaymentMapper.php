<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\ClientPaymentData;
use App\Integrations\Sisahygo\V1\DTO\PaginationMeta;
use App\Integrations\Sisahygo\V1\DTO\PaymentListResult;
use App\Integrations\Sisahygo\V1\DTO\PaymentPartyData;
use App\Integrations\Sisahygo\V1\DTO\PaymentReferenceData;
use App\Integrations\Sisahygo\V1\DTO\PaymentSummaryData;
use Carbon\CarbonImmutable;
use Throwable;

class PaymentMapper
{
    /** @param array<string, mixed> $data */
    public function payment(array $data): ClientPaymentData
    {
        $identifier = $this->nullableString($data['payment_identifier'] ?? null);

        if (! $identifier) {
            throw new SisahygoUnexpectedResponseException('Payment response is missing payment_identifier.');
        }

        return new ClientPaymentData(
            paymentIdentifier: $identifier,
            source: $this->nullableString($data['source'] ?? null),
            paymentType: $this->nullableString($data['payment_type'] ?? $data['paymenttype'] ?? null),
            payerRole: $this->nullableString($data['payer_role'] ?? null),
            orderHeaderNo: $this->nullableString($data['order_header_no'] ?? null),
            orderHeaderDate: $this->date($data['order_header_date'] ?? null),
            clientReferenceNo: $this->nullableString($data['client_reference_no'] ?? null),
            billingDate: $this->date($data['billing_date'] ?? null),
            paymentDate: $this->date($data['payment_date'] ?? null),
            paymentStatus: $this->status($data['payment_status'] ?? null),
            totalAmount: $this->money($data['total_amount'] ?? $data['amount'] ?? $data['bal_amount'] ?? null),
            paidAmount: $this->money($data['paid_amount'] ?? $data['pay_amount'] ?? null),
            outstandingAmount: $this->money($data['outstanding_amount'] ?? $data['balance_amount'] ?? null),
            discountAmount: $this->money($data['discount_amount'] ?? null),
            taxAmount: $this->money($data['tax_amount'] ?? null),
            invoice: $this->reference($data, 'invoice'),
            receipt: $this->reference($data, 'receipt'),
            sender: $this->party($data, 'sender'),
            receiver: $this->party($data, 'receiver'),
            trackingReference: $this->nullableString($data['tracking_reference'] ?? $data['waybill_no'] ?? $data['tracking_no'] ?? null),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>|null  $summary
     * @param  array<string, mixed>|null  $meta
     */
    public function listResult(array $items, ?array $summary = null, ?array $meta = null): PaymentListResult
    {
        return new PaymentListResult(
            payments: array_map(fn (array $item) => $this->payment($item), $items),
            summary: $this->summary($summary),
            pagination: $this->pagination($meta),
        );
    }

    /** @param array<string, mixed>|null $data */
    private function summary(?array $data): PaymentSummaryData
    {
        if (! $data) {
            return new PaymentSummaryData;
        }

        return new PaymentSummaryData(
            recordCount: $this->int($data['record_count'] ?? null),
            totalAmount: $this->money($data['total_amount'] ?? null),
            paidRecordCount: $this->int($data['paid_record_count'] ?? null),
            outstandingRecordCount: $this->int($data['outstanding_record_count'] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    private function reference(array $data, string $type): PaymentReferenceData
    {
        $nested = is_array($data[$type] ?? null) ? $data[$type] : [];

        return new PaymentReferenceData(
            number: $this->nullableString($nested['number'] ?? $nested['no'] ?? $data["{$type}_number"] ?? $data["{$type}_no"] ?? null),
            date: $this->date($nested['date'] ?? $data["{$type}_date"] ?? null),
        );
    }

    /** @param array<string, mixed> $data */
    private function party(array $data, string $type): PaymentPartyData
    {
        $nested = is_array($data[$type] ?? null) ? $data[$type] : [];
        $prefix = $type === 'sender' ? 'sender' : 'receiver';
        $legacyPrefix = $type === 'sender' ? 'customer' : 'customer_rec';

        return new PaymentPartyData(
            customerId: $this->nullableInt($nested['customer_id'] ?? $data["{$prefix}_customer_id"] ?? $data["{$legacyPrefix}_id"] ?? null),
            name: $this->nullableString($nested['name'] ?? $data["{$prefix}_name"] ?? $data[$legacyPrefix] ?? null),
            code: $this->nullableString($nested['code'] ?? $data["{$prefix}_code"] ?? null),
            branchName: $this->nullableString($nested['branch_name'] ?? $data["{$prefix}_branch_name"] ?? null),
        );
    }

    /** @param array<string, mixed>|null $meta */
    private function pagination(?array $meta): ?PaginationMeta
    {
        if (! $meta) {
            return null;
        }

        return new PaginationMeta(
            currentPage: max(1, (int) ($meta['current_page'] ?? 1)),
            perPage: max(1, (int) ($meta['per_page'] ?? 15)),
            total: is_numeric($meta['total'] ?? null) ? (int) $meta['total'] : null,
            lastPage: is_numeric($meta['last_page'] ?? null) ? (int) $meta['last_page'] : null,
        );
    }

    private function status(mixed $value): ?string
    {
        return match (true) {
            $value === 1, $value === '1', $value === 'paid' => 'paid',
            $value === 0, $value === '0', $value === 'outstanding', $value === 'unpaid' => 'outstanding',
            default => $this->nullableString($value),
        };
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function money(mixed $value): ?string
    {
        if (! is_numeric($value) && ! is_string($value)) {
            return null;
        }

        $value = trim((string) $value);

        return preg_match('/^[+-]?\d+(\.\d+)?$/', $value) ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
