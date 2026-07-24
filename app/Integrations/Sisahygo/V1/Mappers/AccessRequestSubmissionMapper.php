<?php

namespace App\Integrations\Sisahygo\V1\Mappers;

use App\Integrations\Sisahygo\Exceptions\SisahygoUnexpectedResponseException;
use App\Integrations\Sisahygo\V1\DTO\AccessRequestSubmissionResult;

class AccessRequestSubmissionMapper
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function map(array $data, array $meta = []): AccessRequestSubmissionResult
    {
        if (! is_string($data['request_no'] ?? null) || ! is_string($data['connect_reference'] ?? null) || ! is_string($data['status'] ?? null)) {
            throw new SisahygoUnexpectedResponseException('Access request response is missing expected fields.');
        }

        return new AccessRequestSubmissionResult(
            requestNo: $data['request_no'],
            publicId: is_string($data['public_id'] ?? null) ? $data['public_id'] : null,
            connectReference: $data['connect_reference'],
            status: $data['status'],
            statusLabel: is_string($data['status_label'] ?? null) ? $data['status_label'] : null,
            submittedAt: is_string($data['submitted_at'] ?? null) ? $data['submitted_at'] : null,
            duplicate: (bool) ($meta['duplicate'] ?? false),
        );
    }
}
