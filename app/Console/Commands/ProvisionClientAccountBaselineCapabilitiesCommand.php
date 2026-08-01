<?php

namespace App\Console\Commands;

use App\Application\ClientAccounts\ProvisionBaselineClientAccountCapabilities;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use Illuminate\Console\Command;

class ProvisionClientAccountBaselineCapabilitiesCommand extends Command
{
    protected $signature = 'client-account:provision-baseline-capabilities
        {account : Existing Client Account ID or code}
        {--dry-run : Show missing capabilities without changing data}';

    protected $description = 'Add the baseline Sisahygo Connect business capabilities to an active Client Account.';

    public function handle(ProvisionBaselineClientAccountCapabilities $provisioner): int
    {
        $account = $this->resolveClientAccount((string) $this->argument('account'));

        if (! $account) {
            $this->error('Client Account was not found.');

            return self::FAILURE;
        }

        if ($account->status !== ClientAccountStatus::Active) {
            $this->error('Client Account must be active before baseline capabilities can be provisioned.');

            return self::FAILURE;
        }

        $present = $this->presentCapabilities($account);
        $missing = $provisioner->missingFor($account);

        $this->line('Client Account: '.$account->code.' - '.$account->name);
        $this->table(['Currently present capabilities'], $this->rows($present));

        if ($missing === []) {
            $this->info('No baseline capabilities are missing.');

            return self::SUCCESS;
        }

        $missingValues = array_map(fn ($capability) => $capability->value, $missing);
        $this->table(['Missing baseline capabilities'], $this->rows($missingValues));
        $this->table(['Capabilities to add'], $this->rows($missingValues));

        if ($this->option('dry-run')) {
            $this->warn('Dry run only. No capabilities were changed.');

            return self::SUCCESS;
        }

        $added = $provisioner->provision($account);
        $this->info('Added '.count($added).' baseline capabilities.');

        return self::SUCCESS;
    }

    private function resolveClientAccount(string $identifier): ?ClientAccount
    {
        return ClientAccount::query()
            ->when(is_numeric($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! is_numeric($identifier), fn ($query) => $query->where('code', $identifier))
            ->first();
    }

    /** @return list<string> */
    private function presentCapabilities(ClientAccount $account): array
    {
        return ClientAccountCapability::query()
            ->where('client_account_id', $account->id)
            ->orderBy('capability')
            ->pluck('capability')
            ->map(fn ($capability) => is_string($capability) ? $capability : $capability->value)
            ->all();
    }

    /**
     * @param list<string> $values
     * @return list<array{0: string}>
     */
    private function rows(array $values): array
    {
        if ($values === []) {
            return [['(none)']];
        }

        return array_map(fn (string $value): array => [$value], $values);
    }
}
