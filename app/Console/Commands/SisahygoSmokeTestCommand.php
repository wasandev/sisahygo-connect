<?php

namespace App\Console\Commands;

use App\Application\Dashboard\GetCustomerDashboard;
use App\Application\History\ListOrderHistory;
use App\Application\OrderChecking\SubmitSingleOrderChecking;
use App\Application\Payment\PaymentQueryService;
use App\Application\Search\ResolveUniversalSearch;
use App\Application\Shipment\ShipmentQueryService;
use App\Application\System\CheckSisahygoApiConnectivity;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Integrations\Sisahygo\V1\Endpoints\UnitsEndpoint;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class SisahygoSmokeTestCommand extends Command
{
    protected $signature = 'sisahygo:smoke-test
        {--account= : Client Account ID or code}
        {--user= : Optional user ID for the smoke test}
        {--search= : Optional universal search query}
        {--include-write : Include controlled order creation smoke test}
        {--confirm-write : Explicitly confirm controlled write test}
        {--receiver-id= : Receiver customer ID for controlled write test}
        {--product-id= : Product ID for controlled write test}
        {--unit-id= : Unit ID for controlled write test}
        {--amount=1 : Item amount for controlled write test}';

    protected $description = 'Run sanitized read-only Sisahygo sandbox smoke checks, with explicitly gated write checks.';

    private int $failures = 0;

    public function handle(
        ClientAccountAuthorizationService $authorization,
        CheckSisahygoApiConnectivity $connectivity,
        SisahygoIntegrationContextBuilder $contextBuilder,
        UnitsEndpoint $units,
        GetCustomerDashboard $dashboard,
        ShipmentQueryService $shipments,
        ListOrderHistory $history,
        PaymentQueryService $payments,
        ResolveUniversalSearch $search,
        SubmitSingleOrderChecking $singleOrder,
    ): int {
        $account = $this->resolveAccount();
        if (! $account) {
            return self::FAILURE;
        }

        $user = $this->resolveUser($account);
        if (! $user) {
            $this->failCheck('operator_user', 'No active Client Account user is available.');

            return self::FAILURE;
        }

        $this->info('Sisahygo sandbox smoke test');
        $this->line('client_account_id: '.$account->id);
        $this->line('client_account_code: '.$account->code);
        $this->line('operator_user_id: '.$user->id);

        $configuration = $this->checkConfiguration();
        $this->checkConnectivity($connectivity, $user, $account);
        $this->checkUnits($authorization, $contextBuilder, $units, $user, $account);
        $this->checkDashboard($dashboard, $user, $account);
        $this->checkShipments($shipments, $user, $account);
        $this->checkHistory($history, $user, $account);
        $this->checkPayments($authorization, $payments, $user, $account);
        $this->checkUniversalSearch($search, $user, $account);
        $this->checkWrite($configuration, $singleOrder, $user, $account);

        return $this->failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function checkConfiguration(): ?SisahygoApiConfiguration
    {
        try {
            $configuration = SisahygoApiConfiguration::fromConfig();
            $this->pass('configuration', $configuration->environment->value.' host '.(parse_url($configuration->baseUrl, PHP_URL_HOST) ?: 'unavailable'));

            return $configuration;
        } catch (Throwable $exception) {
            $this->failCheck('configuration', $exception->getMessage());

            return null;
        }
    }

    private function checkConnectivity(CheckSisahygoApiConnectivity $connectivity, User $user, ClientAccount $account): void
    {
        $status = $connectivity($user, $account);

        if ($status['status'] === 'connected') {
            $this->pass('connectivity', $status['duration_ms'].' ms');

            return;
        }

        $this->failCheck('connectivity', (string) ($status['message'] ?? $status['status']));
    }

    private function checkUnits(ClientAccountAuthorizationService $authorization, SisahygoIntegrationContextBuilder $contextBuilder, UnitsEndpoint $units, User $user, ClientAccount $account): void
    {
        if (! $authorization->userCan($user, $account, ClientCapability::SettingsManage)) {
            $this->skip('units', 'settings.manage capability missing');

            return;
        }

        try {
            $items = $units->list($contextBuilder->build($user, $account, ClientCapability::SettingsManage));
            $this->pass('units', count($items).' units visible');
        } catch (Throwable $exception) {
            $this->failCheck('units', $this->safeError($exception));
        }
    }

    private function checkDashboard(GetCustomerDashboard $dashboard, User $user, ClientAccount $account): void
    {
        try {
            $result = $dashboard($user, $account);
            $this->pass('dashboard', count($result['summary_cards'] ?? []).' summary cards');
        } catch (Throwable $exception) {
            $this->failCheck('dashboard', $this->safeError($exception));
        }
    }

    private function checkShipments(ShipmentQueryService $shipments, User $user, ClientAccount $account): void
    {
        try {
            $result = $shipments->list($user, $account, ['page' => 1, 'per_page' => 1]);
            $this->pass('shipments', 'meta total '.($result['meta']['total'] ?? 'unknown'));
        } catch (Throwable $exception) {
            $this->failCheck('shipments', $this->safeError($exception));
        }
    }

    private function checkHistory(ListOrderHistory $history, User $user, ClientAccount $account): void
    {
        try {
            $result = $history($user, $account, ['page' => 1, 'per_page' => 1]);
            $this->pass('order_history', 'meta total '.($result['meta']['total'] ?? 'unknown'));
        } catch (Throwable $exception) {
            $this->failCheck('order_history', $this->safeError($exception));
        }
    }

    private function checkPayments(ClientAccountAuthorizationService $authorization, PaymentQueryService $payments, User $user, ClientAccount $account): void
    {
        if (! $authorization->userCan($user, $account, ClientCapability::PaymentView)) {
            $this->skip('payments', 'payment.view capability missing');

            return;
        }

        try {
            $result = $payments->list($user, $account, ['page' => 1, 'per_page' => 5]);
            $this->pass('payments', 'records '.($result['meta']['total'] ?? count($result['items'] ?? [])));
        } catch (Throwable $exception) {
            $this->failCheck('payments', $this->safeError($exception));
        }
    }

    private function checkUniversalSearch(ResolveUniversalSearch $search, User $user, ClientAccount $account): void
    {
        $query = $this->option('search');

        if (blank($query)) {
            $this->skip('universal_search', 'no --search query provided');

            return;
        }

        try {
            $result = $search($user, $account, (string) $query);
            $result['found'] ? $this->pass('universal_search', 'resolved to '.$result['target_parameters'][0]) : $this->skip('universal_search', 'no matching record');
        } catch (Throwable $exception) {
            $this->failCheck('universal_search', $this->safeError($exception));
        }
    }

    private function checkWrite(?SisahygoApiConfiguration $configuration, SubmitSingleOrderChecking $singleOrder, User $user, ClientAccount $account): void
    {
        if (! $this->option('include-write')) {
            $this->skip('write_order_checking', 'write checks not requested');

            return;
        }

        if (! $configuration || $configuration->environment !== SisahygoApiEnvironment::Sandbox) {
            $this->failCheck('write_order_checking', 'write smoke test is allowed only in sandbox');

            return;
        }

        if (! in_array((string) config('app.env'), ['local', 'testing', 'staging'], true)) {
            $this->failCheck('write_order_checking', 'write smoke test is refused for this application environment');

            return;
        }

        if (SisahygoApiConfiguration::host($configuration->baseUrl) === SisahygoApiConfiguration::host((string) config('sisahygo.api.environments.production.base_url'))) {
            $this->failCheck('write_order_checking', 'write smoke test refuses the production API endpoint');

            return;
        }

        if (! $this->option('confirm-write')) {
            $this->failCheck('write_order_checking', 'write checks require --confirm-write');

            return;
        }

        $receiverId = (int) $this->option('receiver-id');
        $productId = (int) $this->option('product-id');
        $unitId = (int) $this->option('unit-id');

        if ($receiverId <= 0 || $productId <= 0 || $unitId <= 0) {
            $this->failCheck('write_order_checking', 'controlled fixture requires --receiver-id, --product-id, and --unit-id');

            return;
        }

        $reference = $this->smokeReferencePrefix().now()->format('YmdHis').'-'.strtoupper(str()->random(6));

        try {
            $singleOrder->submit($user, $account, null, $receiverId, $reference, 'Sandbox smoke test', [[
                'product_id' => $productId,
                'unit_id' => $unitId,
                'amount' => (string) $this->option('amount'),
                'client_line_id' => $reference.'-1',
            ]]);

            $this->pass('write_order_checking', 'created client_reference_no '.$reference);
        } catch (Throwable $exception) {
            $this->failCheck('write_order_checking', $this->safeError($exception).' reference '.$reference);
        }
    }

    private function resolveAccount(): ?ClientAccount
    {
        $identifier = $this->option('account');

        if (blank($identifier)) {
            $this->failCheck('account', 'The --account option is required.');

            return null;
        }

        $account = ClientAccount::query()
            ->when(is_numeric($identifier), fn ($query) => $query->whereKey((int) $identifier))
            ->when(! is_numeric($identifier), fn ($query) => $query->where('code', (string) $identifier))
            ->first();

        if (! $account) {
            $this->failCheck('account', 'Client Account was not found.');
        }

        return $account;
    }

    private function resolveUser(ClientAccount $account): ?User
    {
        $userId = $this->option('user');

        if (filled($userId)) {
            return User::query()
                ->whereKey((int) $userId)
                ->whereHas('clientAccountMemberships', fn ($query) => $query->where('client_account_id', $account->id)->where('is_active', true))
                ->first();
        }

        return ClientAccountUser::query()
            ->where('client_account_id', $account->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first()?->user;
    }

    private function safeError(Throwable $exception): string
    {
        if ($exception instanceof SisahygoApiException) {
            return class_basename($exception).' status '.($exception->status ?? 'none');
        }

        return class_basename($exception);
    }

    private function smokeReferencePrefix(): string
    {
        return ((string) config('app.env')) === 'staging' ? 'STG-SMOKE-' : 'SBX-SMOKE-';
    }

    private function pass(string $check, string $message): void
    {
        $this->line('PASS '.$check.': '.$message);
    }

    private function skip(string $check, string $message): void
    {
        $this->line('SKIP '.$check.': '.$message);
    }

    private function failCheck(string $check, string $message): void
    {
        $this->failures++;
        $this->line('FAIL '.$check.': '.$message);
    }
}
