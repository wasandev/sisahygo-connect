<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingResultRowData
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public int $index,
        public ?string $clientReferenceNo,
        public string $status,
        public ?string $trackingNo = null,
        public ?string $orderStatus = null,
        public ?string $apiBatchNo = null,
        public ?string $apiBatchReferenceNo = null,
        public ?string $apiBatchDate = null,
        public ?string $message = null,
        public ?string $errorCode = null,
        public array $details = [],
        public ?string $correlationId = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === 'success';
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'index' => $this->index,
            'client_reference_no' => $this->clientReferenceNo,
            'status' => $this->status,
            'tracking_no' => $this->trackingNo,
            'order_status' => $this->orderStatus,
            'api_batch_no' => $this->apiBatchNo,
            'api_batch_reference_no' => $this->apiBatchReferenceNo,
            'api_batch_date' => $this->apiBatchDate,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'details' => $this->details,
            'correlation_id' => $this->correlationId,
        ];
    }
}
