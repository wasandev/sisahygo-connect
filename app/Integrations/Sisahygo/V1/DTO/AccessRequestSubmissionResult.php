<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class AccessRequestSubmissionResult
{
    public function __construct(
        public string $requestNo,
        public ?string $publicId,
        public string $connectReference,
        public string $status,
        public ?string $statusLabel,
        public ?string $submittedAt,
        public bool $duplicate,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'request_no' => $this->requestNo,
            'public_id' => $this->publicId,
            'connect_reference' => $this->connectReference,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'submitted_at' => $this->submittedAt,
            'duplicate' => $this->duplicate,
        ];
    }
}
