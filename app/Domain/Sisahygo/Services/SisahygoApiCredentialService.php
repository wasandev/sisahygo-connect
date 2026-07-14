<?php

namespace App\Domain\Sisahygo\Services;

use App\Domain\Audit\Contracts\RecordsClientAccountActivity;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SisahygoApiCredentialService
{
    public function __construct(private readonly RecordsClientAccountActivity $activityLogger) {}

    public function create(
        ClientAccount $clientAccount,
        SisahygoApiEnvironment $environment,
        string $name,
        string $apiKey,
        ?User $createdBy = null,
    ): SisahygoApiCredential {
        return DB::transaction(function () use ($clientAccount, $environment, $name, $apiKey, $createdBy): SisahygoApiCredential {
            $previous = $this->activeForAccount($clientAccount, $environment);

            if ($previous) {
                $previous->forceFill([
                    'status' => SisahygoCredentialStatus::Revoked,
                    'active_slot' => null,
                    'revoked_at' => now(),
                ])->save();
            }

            $credential = SisahygoApiCredential::query()->create([
                'client_account_id' => $clientAccount->id,
                'environment' => $environment,
                'name' => $name,
                'encrypted_api_key' => $apiKey,
                'key_fingerprint' => SisahygoApiCredential::fingerprint($apiKey),
                'status' => SisahygoCredentialStatus::Active,
                'active_slot' => 'active',
                'rotated_from_id' => $previous?->id,
                'created_by' => $createdBy?->id,
            ]);

            $this->activityLogger->record($clientAccount, 'sisahygo.credential.created', $createdBy, $credential, [
                'credential_id' => $credential->id,
                'environment' => $environment->value,
                'fingerprint' => $credential->key_fingerprint,
            ]);

            return $credential;
        });
    }

    public function resolveActive(ClientAccount $clientAccount, SisahygoApiEnvironment $environment): SisahygoApiCredential
    {
        $credential = $this->activeForAccount($clientAccount, $environment);

        if (! $credential) {
            $this->activityLogger->record($clientAccount, 'sisahygo.credential.resolution_failed', metadata: [
                'environment' => $environment->value,
            ]);

            throw (new ModelNotFoundException())->setModel(SisahygoApiCredential::class);
        }

        return $credential;
    }

    public function credentialForAccount(ClientAccount $clientAccount, int $credentialId): SisahygoApiCredential
    {
        return SisahygoApiCredential::query()
            ->whereKey($credentialId)
            ->where('client_account_id', $clientAccount->id)
            ->firstOrFail();
    }

    public function markLastUsed(SisahygoApiCredential $credential): void
    {
        $credential->forceFill(['last_used_at' => now()])->save();
    }

    public function revoke(SisahygoApiCredential $credential, ?User $revokedBy = null): void
    {
        $credential->forceFill([
            'status' => SisahygoCredentialStatus::Revoked,
            'active_slot' => null,
            'revoked_at' => now(),
        ])->save();

        $this->activityLogger->record($credential->clientAccount, 'sisahygo.credential.revoked', $revokedBy, $credential, [
            'credential_id' => $credential->id,
            'environment' => $credential->environment->value,
            'fingerprint' => $credential->key_fingerprint,
        ]);
    }

    private function activeForAccount(ClientAccount $clientAccount, SisahygoApiEnvironment $environment): ?SisahygoApiCredential
    {
        return SisahygoApiCredential::query()
            ->where('client_account_id', $clientAccount->id)
            ->where('environment', $environment->value)
            ->where('status', SisahygoCredentialStatus::Active->value)
            ->where('active_slot', 'active')
            ->first();
    }
}