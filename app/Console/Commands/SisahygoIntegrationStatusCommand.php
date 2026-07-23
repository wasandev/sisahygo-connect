<?php

namespace App\Console\Commands;

use App\Application\System\CheckSisahygoApiConnectivity;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class SisahygoIntegrationStatusCommand extends Command
{
    protected $signature = 'sisahygo:integration-status {--account= : Client Account ID or code} {--user= : Optional user ID for the check}';

    protected $description = 'Show sanitized Sisahygo Core API configuration, credential, and connectivity status.';

    public function handle(CheckSisahygoApiConnectivity $connectivity): int
    {
        $account = $this->resolveAccount();

        if (! $account) {
            return self::FAILURE;
        }

        $user = $this->resolveUser($account);

        if (! $user) {
            $this->failLine('operator_user', 'No active Client Account user is available for the check.');

            return self::FAILURE;
        }

        $this->info('Sisahygo integration status');
        $this->line('client_account_id: '.$account->id);
        $this->line('client_account_code: '.$account->code);
        $this->line('operator_user_id: '.$user->id);

        try {
            $configuration = SisahygoApiConfiguration::fromConfig();
            $this->passLine('configuration', 'configured');
            $this->line('api_environment: '.$configuration->environment->value);
            $this->line('api_host: '.(parse_url($configuration->baseUrl, PHP_URL_HOST) ?: 'unavailable'));
        } catch (Throwable $exception) {
            $this->failLine('configuration', $exception->getMessage());

            return self::FAILURE;
        }

        $credential = SisahygoApiCredential::query()
            ->where('client_account_id', $account->id)
            ->where('environment', $configuration->environment)
            ->where('status', SisahygoCredentialStatus::Active)
            ->where('active_slot', 'active')
            ->whereNull('revoked_at')
            ->first();

        if (! $credential) {
            $this->failLine('credential', 'No active credential for selected account and environment.');

            return self::FAILURE;
        }

        $this->passLine('credential', 'active');
        $this->line('credential_id: '.$credential->id);
        $this->line('credential_fingerprint: '.$credential->key_fingerprint);

        $status = $connectivity($user, $account);
        $line = $status['status'].' in '.$status['duration_ms'].' ms at '.$status['checked_at'];

        if ($status['status'] === 'connected') {
            $this->passLine('connectivity', $line);

            return self::SUCCESS;
        }

        $this->failLine('connectivity', trim($line.' '.($status['message'] ?? '')));

        return self::FAILURE;
    }

    private function resolveAccount(): ?ClientAccount
    {
        $identifier = $this->option('account');

        if (blank($identifier)) {
            $this->failLine('account', 'The --account option is required.');

            return null;
        }

        $account = ClientAccount::query()
            ->when(is_numeric($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! is_numeric($identifier), fn ($query) => $query->where('code', (string) $identifier))
            ->first();

        if (! $account) {
            $this->failLine('account', 'Client Account was not found.');
        }

        return $account;
    }

    private function resolveUser(ClientAccount $account): ?User
    {
        $userId = $this->option('user');

        if (filled($userId)) {
            return User::query()
                ->whereKey((int) $userId)
                ->whereHas('clientAccountMemberships', fn ($query) => $query
                    ->where('client_account_id', $account->id)
                    ->where('is_active', true))
                ->first();
        }

        return ClientAccountUser::query()
            ->where('client_account_id', $account->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first()?->user;
    }

    private function passLine(string $check, string $message): void
    {
        $this->line('PASS '.$check.': '.$message);
    }

    private function failLine(string $check, string $message): void
    {
        $this->line('FAIL '.$check.': '.$message);
    }
}
