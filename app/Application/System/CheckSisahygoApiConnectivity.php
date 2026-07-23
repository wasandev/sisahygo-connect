<?php

namespace App\Application\System;

use App\Application\Integration\SisahygoApiErrorMessage;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\Endpoints\UnitsEndpoint;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $credentialExists = SisahygoApiCredential::query()
            ->where('client_account_id', $clientAccount->id)
            ->where('environment', $configuration->environment)
            ->where('status', SisahygoCredentialStatus::Active)
            ->whereNull('revoked_at')
            ->exists();

        if (! $credentialExists) {
            return $this->result(true, false, 'credential_missing', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.credential'));
        }

        try {
            $context = app(SisahygoIntegrationContextBuilder::class)->build($user, $clientAccount, ClientCapability::SettingsManage);
            app(UnitsEndpoint::class)->list($context);

            return $this->result(true, true, 'connected', $configuration->environment->value, $startedAt, $checkedAt);
        } catch (AuthorizationException|ModelNotFoundException) {
            return $this->result(true, $credentialExists, 'unavailable', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.authorization'));
        } catch (SisahygoApiException $exception) {
            return $this->result(true, $credentialExists, 'unavailable', $configuration->environment->value, $startedAt, $checkedAt, app(SisahygoApiErrorMessage::class)->message($exception, 'client_account.api_status'));
        } catch (Throwable) {
            return $this->result(true, $credentialExists, 'unavailable', $configuration->environment->value, $startedAt, $checkedAt, __('client_account.api_status.errors.unexpected'));
        }
    }

    /** @return array<string, mixed> */
    private function result(bool $configurationExists, bool $credentialExists, string $status, ?string $environment, float $startedAt, string $checkedAt, ?string $message = null): array
    {
        return [
            'configuration_exists' => $configurationExists,
            'credential_exists' => $credentialExists,
            'status' => $status,
            'environment' => $environment,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'checked_at' => $checkedAt,
            'message' => $message,
        ];
    }
}
