<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class BulkOrderCheckingSummaryData
{
    public function __construct(
        public int $total,
        public int $success,
        public int $failed,
    ) {}

    /** @return array{total: int, success: int, failed: int} */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'success' => $this->success,
            'failed' => $this->failed,
        ];
    }
}
