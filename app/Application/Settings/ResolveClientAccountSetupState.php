<?php

namespace App\Application\Settings;

use App\Application\System\CheckSisahygoApiConnectivity;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Models\User;

class ResolveClientAccountSetupState
{
    public function __construct(
        private readonly CheckSisahygoApiConnectivity $connectivity,
        private readonly SisahygoApiCredentialSetup $credentialSetup,
    ) {}

    public function __invoke(User $user, ClientAccount $clientAccount, bool $checkConnectivity = true): ClientAccountSetupState
    {
        $clientAccount->loadMissing(['memberships', 'customerLinks']);

        $canManageSettings = $user->can('manageSettings', $clientAccount);
        $credential = $this->credentialSetup->activeCredential($clientAccount);
        $connectivity = $checkConnectivity
            ? ($this->connectivity)($user, $clientAccount)
            : ['status' => $credential ? 'not_checked' : 'credential_missing', 'environment' => $credential?->environment->value];

        $stageCompletion = [
            'account_created' => $user->exists,
            'client_account_created' => $clientAccount->exists && $clientAccount->status === ClientAccountStatus::Active,
            'membership_ready' => $clientAccount->memberships
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->isNotEmpty(),
            'customer_mapping_ready' => $clientAccount->customerLinks
                ->where('is_active', true)
                ->isNotEmpty(),
            'credential_configured' => $credential?->isActive() ?? false,
            'api_connected' => ($connectivity['status'] ?? null) === 'connected',
        ];

        $firstIncomplete = array_search(false, $stageCompletion, true);

        $steps = collect($stageCompletion)
            ->map(function (bool $complete, string $key) use ($firstIncomplete): array {
                return [
                    'key' => $key,
                    'label' => __("onboarding.setup_steps.{$key}"),
                    'status' => $complete ? 'complete' : ($firstIncomplete === $key ? 'current' : 'future'),
                ];
            })
            ->values()
            ->all();

        return new ClientAccountSetupState(
            steps: $steps,
            connectivity: $connectivity,
            canManageSettings: $canManageSettings,
            clientAccountName: $clientAccount->name,
            nextActionKey: $firstIncomplete ?: null,
            environment: $connectivity['environment'] ?? $credential?->environment->value,
            credentialStatus: $credential?->status instanceof SisahygoCredentialStatus ? $credential->status->value : null,
            fingerprint: $credential ? $this->shortFingerprint($credential->key_fingerprint) : null,
            lastUsedAt: $credential?->last_used_at?->format('Y-m-d H:i:s'),
        );
    }

    private function shortFingerprint(string $fingerprint): string
    {
        return substr($fingerprint, 0, 8).'...'.substr($fingerprint, -8);
    }
}
