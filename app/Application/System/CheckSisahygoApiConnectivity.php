<?php

namespace App\Application\System;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class CheckSisahygoApiConnectivity
{
    /** @return array<string, mixed> */
    public function __invoke(User $user, ClientAccount $clientAccount): array
    {
        $startedAt = microtime(true);
        $checkedAt = now(config('app.timezone'))->format('Y-m-d H:i:s');

        try {
            $configuration = SisahygoApiConfiguration::fromConfig();
        } catch (Throwable) {
            return $this->result(false, false, 'configuration_missing', null, $startedAt, $checkedAt, __('client_account.api_status.errors.configuration'));
        }

        $credential = SisahygoApiCredential::query()
            ->where('client_account_id', $clientAccount->id)
            ->where('environment', $configuration->environment)
            ->where('status', SisahygoCredentialStatus::Active)
            ->where('active_slot', 'active')
            ->whereNull('revoked_at')
            ->first();

        if (! $credential) {
            return $this->result(true, false, 'credential_missing', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.credential'));
        }

        try {
            $response = Http::baseUrl($configuration->baseUrl)
                ->acceptJson()
                ->asJson()
                ->connectTimeout($configuration->connectTimeout)
                ->timeout($configuration->timeout)
                ->withUserAgent($configuration->userAgent)
                ->withHeaders([
                    'X-Api-Key' => $credential->apiKey(),
                    'X-Correlation-ID' => (string) Str::uuid(),
                ])
                ->get('ping');

            $payload = $response->json();

            if ($response->successful() && is_array($payload) && data_get($payload, 'data.status') === 'ok') {
                return $this->result(true, true, 'connected', $configuration->environment->value, $startedAt, $checkedAt, fingerprint: $credential->key_fingerprint, lastUsedAt: $credential->last_used_at?->format('Y-m-d H:i:s'));
            }

            $status = match ($response->status()) {
                401 => 'invalid_credential',
                403 => 'api_client_inactive',
                429 => 'rate_limited',
                default => $response->serverError() ? 'core_unavailable' : 'malformed_response',
            };

            return $this->result(true, true, $status, $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.'.$status), fingerprint: $credential->key_fingerprint, lastUsedAt: $credential->last_used_at?->format('Y-m-d H:i:s'));
        } catch (ConnectionException $exception) {
            $message = Str::lower($exception->getMessage());
            $status = str_contains($message, 'timed out') || str_contains($message, 'timeout')
                ? 'timeout'
                : 'core_unavailable';

            return $this->result(true, true, $status, $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.'.$status), fingerprint: $credential->key_fingerprint, lastUsedAt: $credential->last_used_at?->format('Y-m-d H:i:s'));
        } catch (RequestException) {
            return $this->result(true, true, 'unknown_error', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.unknown_error'), fingerprint: $credential->key_fingerprint, lastUsedAt: $credential->last_used_at?->format('Y-m-d H:i:s'));
        } catch (Throwable) {
            return $this->result(true, true, 'unknown_error', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.unknown_error'), fingerprint: $credential->key_fingerprint, lastUsedAt: $credential->last_used_at?->format('Y-m-d H:i:s'));
        }
    }

    /** @return array<string, mixed> */
    private function result(bool $configurationExists, bool $credentialExists, string $status, ?string $environment, float $startedAt, string $checkedAt, ?string $message = null, ?string $fingerprint = null, ?string $lastUsedAt = null): array
    {
        return [
            'configuration_exists' => $configurationExists,
            'credential_exists' => $credentialExists,
            'status' => $status,
            'environment' => $environment,
            'fingerprint' => $fingerprint ? substr($fingerprint, 0, 8).'...'.substr($fingerprint, -8) : null,
            'last_used_at' => $lastUsedAt,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'checked_at' => $checkedAt,
            'message' => $message,
        ];
    }
}
