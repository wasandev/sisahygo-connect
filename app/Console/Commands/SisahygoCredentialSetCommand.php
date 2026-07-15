<?php

namespace App\Console\Commands;

use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use Illuminate\Console\Command;
use InvalidArgumentException;

class SisahygoCredentialSetCommand extends Command
{
    protected $signature = 'sisahygo:credential:set
        {--account= : Existing Client Account ID or code}
        {--environment=sandbox : Credential environment: sandbox or production}
        {--name= : Safe display name for the credential}';

    protected $description = 'Provision an encrypted Sisahygo API credential for a Client Account in local development.';

    public function handle(
        SisahygoApiCredentialService $credentials,
        ClientAccountAuthorizationService $authorization,
    ): int {
        if (! app()->environment('local')) {
            $this->error('This command is available only in the local environment.');

            return self::FAILURE;
        }

        $environment = $this->resolveEnvironment();

        if (! $environment) {
            return self::FAILURE;
        }

        $baseUrl = $this->resolveBaseUrl($environment);

        if (! $baseUrl) {
            return self::FAILURE;
        }

        $account = $this->resolveClientAccount();

        if (! $account || ! $this->validateClientAccount($account, $authorization)) {
            return self::FAILURE;
        }

        $activeCredential = $this->activeCredential($account, $environment);

        if ($activeCredential && ! $this->confirmReplacement($account, $environment)) {
            $this->warn('No credential was changed.');

            return self::FAILURE;
        }

        $apiKey = $this->secret('Sisahygo API key');

        if (blank($apiKey)) {
            $this->error('API key cannot be empty.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: 'Local '.$environment->value.' credential';

        try {
            $credential = $credentials->create($account, $environment, $name, $apiKey);
        } finally {
            $apiKey = null;
            unset($apiKey);
        }

        $this->info('Sisahygo credential stored securely.');
        $this->line('Client Account: '.$account->code.' - '.$account->name);
        $this->line('Environment: '.$environment->value);
        $this->line('Base URL: '.$baseUrl);
        $this->line('Status: '.$credential->status->value);
        $this->line('Fingerprint: '.$credential->key_fingerprint);

        return self::SUCCESS;
    }

    private function resolveEnvironment(): ?SisahygoApiEnvironment
    {
        $environment = SisahygoApiEnvironment::tryFrom((string) $this->option('environment'));

        if (! $environment) {
            $this->error('Unsupported environment. Use sandbox or production.');

            return null;
        }

        return $environment;
    }

    private function resolveBaseUrl(SisahygoApiEnvironment $environment): ?string
    {
        try {
            $baseUrl = SisahygoApiConfiguration::baseUrlForEnvironment($environment);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return null;
        }

        if (
            $environment === SisahygoApiEnvironment::Sandbox
            && $baseUrl !== 'https://sandbox-api.sisahygo.online/api/v1/client'
        ) {
            $this->error('Sandbox base URL must resolve to https://sandbox-api.sisahygo.online/api/v1/client.');

            return null;
        }

        return $baseUrl;
    }

    private function resolveClientAccount(): ?ClientAccount
    {
        $identifier = $this->option('account');

        if (blank($identifier)) {
            $this->table(['ID', 'Code', 'Name', 'Status'], ClientAccount::query()
                ->orderBy('code')
                ->limit(20)
                ->get(['id', 'code', 'name', 'status'])
                ->map(fn (ClientAccount $account) => [
                    $account->id,
                    $account->code,
                    $account->name,
                    $account->status->value,
                ])
                ->all());

            $identifier = $this->ask('Client Account ID or code');
        }

        if (blank($identifier)) {
            $this->error('Client Account is required.');

            return null;
        }

        $account = ClientAccount::query()
            ->when(is_numeric($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! is_numeric($identifier), fn ($query) => $query->where('code', (string) $identifier))
            ->first();

        if (! $account) {
            $this->error('Client Account was not found.');

            return null;
        }

        return $account;
    }

    private function validateClientAccount(ClientAccount $account, ClientAccountAuthorizationService $authorization): bool
    {
        if ($account->status !== ClientAccountStatus::Active) {
            $this->error('Client Account must be active before provisioning a credential.');

            return false;
        }

        $activeLinks = $account->customerLinks()->where('is_active', true);
        $activeLinkCount = (clone $activeLinks)->count();
        $senderLinkCount = (clone $activeLinks)->where('can_send', true)->count();

        if ($activeLinkCount === 0) {
            $this->error('Client Account must have at least one active customer link.');

            return false;
        }

        if (! $authorization->hasCapability($account, ClientCapability::ShipmentView)) {
            $this->error('Client Account is missing required capability: '.ClientCapability::ShipmentView->value);

            return false;
        }

        $this->line('Readiness: active customer links present.');
        $this->line('Readiness: '.ClientCapability::ShipmentView->value.' present.');

        if ($authorization->hasCapability($account, ClientCapability::ShipmentHistory)) {
            $this->line('Readiness: '.ClientCapability::ShipmentHistory->value.' present.');
        } else {
            $this->warn('Readiness: '.ClientCapability::ShipmentHistory->value.' is not enabled.');
        }

        if ($senderLinkCount > 0) {
            $this->line('Readiness: authorized sender relationship present for GET /receivers.');
        } else {
            $this->warn('Readiness: no authorized sender relationship found for GET /receivers.');
        }

        return true;
    }

    private function activeCredential(ClientAccount $account, SisahygoApiEnvironment $environment): ?SisahygoApiCredential
    {
        return SisahygoApiCredential::query()
            ->where('client_account_id', $account->id)
            ->where('environment', $environment->value)
            ->where('status', SisahygoCredentialStatus::Active->value)
            ->where('active_slot', 'active')
            ->first();
    }

    private function confirmReplacement(ClientAccount $account, SisahygoApiEnvironment $environment): bool
    {
        return $this->confirm(
            'Replace active '.$environment->value.' credential for '.$account->code.'?',
            false,
        );
    }
}
