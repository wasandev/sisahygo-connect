<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class AccessRequestSubmissionRequest
{
    public function __construct(
        public string $connectReference,
        public string $companyName,
        public string $contactName,
        public string $email,
        public string $phone,
        public string $province,
        public ?string $website,
        public ?int $branchCount,
        public ?string $notes,
        public string $submittedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return array_filter([
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'province' => $this->province,
            'website' => $this->website,
            'branch_count' => $this->branchCount,
            'notes' => $this->notes,
            'connect_reference' => $this->connectReference,
            'submitted_at' => $this->submittedAt,
        ], fn ($value): bool => $value !== null && $value !== '');
    }
}
