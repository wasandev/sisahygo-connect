<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingResponseData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingResultRowData;
use App\Integrations\Sisahygo\V1\DTO\BulkOrderCheckingSummaryData;

class BulkOrderCheckingMapper
{
    /** @param array<string, mixed> $data */
    public function map(array $data): BulkOrderCheckingResponseData
    {
        $summary = $data['summary'] ?? null;
        $results = $data['results'] ?? null;

        if (! is_array($summary) || ! is_array($results)) {
            throw new SisahygoUnexpectedResponseException('Bulk order checking response is missing summary or results.');
        }

        return new BulkOrderCheckingResponseData(
            apiBatchNo: $this->nullableString($data['api_batch_no'] ?? null),
            batchReferenceNo: $this->nullableString($data['batch_reference_no'] ?? null),
            batchDate: $this->nullableString($data['batch_date'] ?? null),
            summary: new BulkOrderCheckingSummaryData(
                total: (int) ($summary['total'] ?? 0),
                success: (int) ($summary['success'] ?? 0),
                failed: (int) ($summary['failed'] ?? 0),
            ),
            results: array_values(array_map(fn (array $row): BulkOrderCheckingResultRowData => $this->row($row), array_filter($results, 'is_array'))),
        );
    }

    /** @param array<string, mixed> $row */
    private function row(array $row): BulkOrderCheckingResultRowData
    {
        $status = $row['status'] ?? null;

        if (! in_array($status, ['success', 'failed'], true)) {
            throw new SisahygoUnexpectedResponseException('Bulk order checking result row has an unknown status.');
        }

        return new BulkOrderCheckingResultRowData(
            index: is_numeric($row['index'] ?? null) ? (int) $row['index'] : 0,
            clientReferenceNo: $this->nullableString($row['client_reference_no'] ?? null),
            status: $status,
            trackingNo: $this->nullableString($row['tracking_no'] ?? null),
            orderStatus: $this->nullableString($row['order_status'] ?? null),
            apiBatchNo: $this->nullableString($row['api_batch_no'] ?? null),
            apiBatchReferenceNo: $this->nullableString($row['api_batch_reference_no'] ?? null),
            apiBatchDate: $this->nullableString($row['api_batch_date'] ?? null),
            message: $this->nullableString($row['message'] ?? null),
            errorCode: $this->nullableString($row['error_code'] ?? null),
            details: is_array($row['details'] ?? null) ? $row['details'] : [],
            correlationId: $this->nullableString($row['correlation_id'] ?? null),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
