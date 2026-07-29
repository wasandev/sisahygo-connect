<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class InvitationPreviewData
{
    public function __construct(
        public string $status,
        public string $email,
        public string $companyName,
        public ?string $contactName,
        public ?string $clientAccountCode,
        public ?string $clientAccountName,
        public string $role,
        public ?string $expiresAt,
        public bool $emailVerifiedByInvitation,
    ) {}

    public function isUsable(): bool
    {
        return in_array($this->status, ['pending', 'sent', 'valid'], true);
    }
}
