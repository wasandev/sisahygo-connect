<?php

namespace App\Integrations\Sisahygo\Support;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;

final readonly class SisahygoIntegrationContext
{
    /**
     * @param array<int, int> $authorizedSenderCustomerIds
     * @param array<int, int> $authorizedReceiverCustomerIds
     */
    public function __construct(
        public int $userId,
        public int $clientAccountId,
        public int $credentialId,
        public string $credentialFingerprint,
        public SisahygoApiEnvironment $environment,
        public array $authorizedSenderCustomerIds,
        public array $authorizedReceiverCustomerIds,
        public ClientCapability $requiredCapability,
        public string $correlationId,
    ) {}

    public function assertSenderCustomerId(int $customerId): void
    {
        abort_unless(in_array($customerId, $this->authorizedSenderCustomerIds, true), 403);
    }

    public function assertReceiverCustomerId(int $customerId): void
    {
        abort_unless(in_array($customerId, $this->authorizedReceiverCustomerIds, true), 403);
    }

    /**
     * @return array<string, mixed>
     */
    public function safeLogContext(): array
    {
        return [
            'user_id' => $this->userId,
            'client_account_id' => $this->clientAccountId,
            'credential_id' => $this->credentialId,
            'credential_fingerprint' => $this->credentialFingerprint,
            'environment' => $this->environment->value,
            'required_capability' => $this->requiredCapability->value,
            'correlation_id' => $this->correlationId,
        ];
    }
}