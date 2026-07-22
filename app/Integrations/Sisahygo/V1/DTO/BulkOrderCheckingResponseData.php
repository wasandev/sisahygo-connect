<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingResponseData
{
    /**
     * @param  array<int, BulkOrderCheckingResultRowData>  $results
     */
    public function __construct(
        public ?string $apiBatchNo,
        public ?string $batchReferenceNo,
        public ?string $batchDate,
        public BulkOrderCheckingSummaryData $summary,
        public array $results,
    ) {}

    public function outcome(): string
    {
        if ($this->summary->failed === 0) {
            return 'all_succeeded';
        }

        if ($this->summary->success === 0) {
            return 'all_failed_processed';
        }

        return 'partial_success';
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'api_batch_no' => $this->apiBatchNo,
            'batch_reference_no' => $this->batchReferenceNo,
            'batch_date' => $this->batchDate,
            'summary' => $this->summary->toArray(),
            'outcome' => $this->outcome(),
            'results' => array_map(fn (BulkOrderCheckingResultRowData $row): array => $row->toSafeArray(), $this->results),
        ];
    }
}
