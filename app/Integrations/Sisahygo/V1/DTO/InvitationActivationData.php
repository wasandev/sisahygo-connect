<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class InvitationActivationData
{
    /**
     * @param array<int, array<string, mixed>> $customerMappings
     * @param array<int, string> $capabilities
     */
    public function __construct(
        public string $status,
        public string $email,
        public string $companyName,
        public ?string $contactName,
        public string $clientAccountCode,
        public string $clientAccountName,
        public string $userRole,
        public bool $emailVerifiedByInvitation,
        public bool $alreadyActivated,
        public array $customerMappings,
        public array $capabilities,
        public mixed $credential,
    ) {}
}
