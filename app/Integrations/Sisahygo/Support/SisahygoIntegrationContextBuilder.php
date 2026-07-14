<?php

namespace App\Integrations\Sisahygo\Support;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;

class SisahygoIntegrationContextBuilder
{
    public function __construct(
        private readonly ClientAccountAuthorizationService $authorization,
        private readonly SisahygoApiCredentialService $credentials,
        private readonly SisahygoApiConfiguration $configuration,
    ) {}

    public function build(User $user, ClientAccount $clientAccount, ClientCapability $requiredCapability): SisahygoIntegrationContext
    {
        $this->validateAccountAndCapability($user, $clientAccount, $requiredCapability);

        $credential = $this->credentials->resolveActive($clientAccount, $this->configuration->environment);

        return $this->fromCredential($user, $clientAccount, $credential, $requiredCapability);
    }

    public function rebuildForQueue(User $user, ClientAccount $clientAccount, int $credentialId, ClientCapability $requiredCapability, string $correlationId): SisahygoIntegrationContext
    {
        $this->validateAccountAndCapability($user, $clientAccount, $requiredCapability);

        $credential = $this->credentials->credentialForAccount($clientAccount, $credentialId);

        if (! $credential->isActive()) {
            throw new AuthorizationException('Sisahygo credential is not active.');
        }

        return $this->fromCredential($user, $clientAccount, $credential, $requiredCapability, $correlationId);
    }

    private function validateAccountAndCapability(User $user, ClientAccount $clientAccount, ClientCapability $requiredCapability): void
    {
        if ($clientAccount->status !== ClientAccountStatus::Active) {
            throw new AuthorizationException('Client Account is not active.');
        }

        if (! $this->authorization->userCan($user, $clientAccount, $requiredCapability)) {
            throw new AuthorizationException('Missing Client Account capability.');
        }
    }

    private function fromCredential(User $user, ClientAccount $clientAccount, SisahygoApiCredential $credential, ClientCapability $requiredCapability, ?string $correlationId = null): SisahygoIntegrationContext
    {
        return new SisahygoIntegrationContext(
            userId: $user->id,
            clientAccountId: $clientAccount->id,
            credentialId: $credential->id,
            credentialFingerprint: $credential->key_fingerprint,
            environment: $credential->environment,
            authorizedSenderCustomerIds: $clientAccount->customerLinks()
                ->where('is_active', true)
                ->where('can_send', true)
                ->pluck('customer_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            authorizedReceiverCustomerIds: $clientAccount->customerLinks()
                ->where('is_active', true)
                ->where('can_receive', true)
                ->pluck('customer_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            requiredCapability: $requiredCapability,
            correlationId: $correlationId ?? (string) Str::uuid(),
        );
    }
}