<?php

namespace App\Application\Settings;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SisahygoApiCredentialSetup
{
    public function __construct(
        private readonly SisahygoApiConfiguration $configuration,
        private readonly SisahygoApiCredentialService $credentials,
    ) {}

    public function activeCredential(ClientAccount $clientAccount): ?SisahygoApiCredential
    {
        return SisahygoApiCredential::query()
            ->where('client_account_id', $clientAccount->id)
            ->where('environment', $this->configuration->environment->value)
            ->where('status', SisahygoCredentialStatus::Active->value)
            ->where('active_slot', 'active')
            ->whereNull('revoked_at')
            ->first();
    }

    public function verify(string $apiKey): SisahygoApiCredentialVerificationResult
    {
        try {
            $response = Http::baseUrl($this->configuration->baseUrl)
                ->acceptJson()
                ->asJson()
                ->connectTimeout($this->configuration->connectTimeout)
                ->timeout($this->configuration->timeout)
                ->withUserAgent($this->configuration->userAgent)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'X-Correlation-ID' => (string) Str::uuid(),
                ])
                ->get('ping');
        } catch (ConnectionException) {
            return SisahygoApiCredentialVerificationResult::failed('connection');
        }

        if ($response->successful() && $response->json('data.status') === 'ok') {
            return SisahygoApiCredentialVerificationResult::verified();
        }

        return match ($response->status()) {
            401 => SisahygoApiCredentialVerificationResult::failed('authentication'),
            403 => SisahygoApiCredentialVerificationResult::failed('authorization'),
            404 => SisahygoApiCredentialVerificationResult::failed('not_found'),
            422 => SisahygoApiCredentialVerificationResult::failed('validation'),
            429 => SisahygoApiCredentialVerificationResult::failed('rate_limited'),
            default => $response->serverError()
                ? SisahygoApiCredentialVerificationResult::failed('server')
                : SisahygoApiCredentialVerificationResult::failed('malformed'),
        };
    }

    public function storeVerified(ClientAccount $clientAccount, string $apiKey, ?User $createdBy = null): SisahygoApiCredential
    {
        $active = $this->activeCredential($clientAccount);

        if ($active && hash_equals($active->key_fingerprint, SisahygoApiCredential::fingerprint($apiKey))) {
            return $active;
        }

        return $this->credentials->create(
            clientAccount: $clientAccount,
            environment: $this->configuration->environment,
            name: 'Core '.$this->configuration->environment->value.' credential',
            apiKey: $apiKey,
            createdBy: $createdBy,
        );
    }
}
