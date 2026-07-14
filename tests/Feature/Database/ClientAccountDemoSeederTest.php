<?php

namespace Tests\Feature\Database;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Enums\CurrentClientAccountResolutionStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Models\User;
use Database\Seeders\Development\ClientAccountDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientAccountDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seeder_is_idempotent(): void
    {
        $this->seed(ClientAccountDemoSeeder::class);

        $counts = $this->demoCounts();

        $this->seed(ClientAccountDemoSeeder::class);

        $this->assertSame($counts, $this->demoCounts());
    }

    public function test_expected_demo_accounts_and_memberships_are_created(): void
    {
        $this->seed(ClientAccountDemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'noaccount@demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'owner@abc-demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'multi@demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'sender@sender-demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'receiver@receiver-demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'viewer@abc-demo.test']);
        $this->assertDatabaseHas('users', ['email' => 'accounting@abc-demo.test']);

        $this->assertDatabaseHas('client_accounts', ['code' => 'SC-DEMO-SINGLE']);
        $this->assertDatabaseHas('client_accounts', ['code' => 'SC-DEMO-SENDER']);
        $this->assertDatabaseHas('client_accounts', ['code' => 'SC-DEMO-RECEIVER']);
        $this->assertDatabaseHas('client_accounts', ['code' => 'SC-DEMO-BOTH']);
        $this->assertDatabaseHas('client_accounts', ['code' => 'SC-DEMO-ACCOUNTING']);

        $multiOwner = User::where('email', 'multi@demo.test')->firstOrFail();
        $this->assertSame(3, ClientAccountUser::where('user_id', $multiOwner->id)->count());
    }

    public function test_external_customer_ids_do_not_require_local_customers_table(): void
    {
        $this->assertFalse(Schema::hasTable('customers'));

        $this->seed(ClientAccountDemoSeeder::class);

        $this->assertDatabaseHas('client_account_customers', ['customer_id' => 10001]);
        $this->assertDatabaseHas('client_account_customers', ['customer_id' => 10002]);
        $this->assertDatabaseHas('client_account_customers', ['customer_id' => 20001]);
        $this->assertDatabaseHas('client_account_customers', ['customer_id' => 20002]);
    }

    public function test_seeded_single_and_multi_account_scenarios_match_resolver_rules(): void
    {
        $this->seed(ClientAccountDemoSeeder::class);

        $resolver = app(CurrentClientAccountResolver::class);

        $noAccount = User::where('email', 'noaccount@demo.test')->firstOrFail();
        $singleOwner = User::where('email', 'owner@abc-demo.test')->firstOrFail();
        $multiOwner = User::where('email', 'multi@demo.test')->firstOrFail();

        $this->assertSame(CurrentClientAccountResolutionStatus::NoAccounts, $resolver->resolve($noAccount)->status);
        $this->assertSame(CurrentClientAccountResolutionStatus::Selected, $resolver->resolve($singleOwner)->status);
        $this->assertSame(CurrentClientAccountResolutionStatus::SelectionRequired, $resolver->resolve($multiOwner)->status);
    }

    public function test_role_and_capability_scenarios_match_access_rules(): void
    {
        $this->seed(ClientAccountDemoSeeder::class);

        $viewer = User::where('email', 'viewer@abc-demo.test')->firstOrFail();
        $accounting = User::where('email', 'accounting@abc-demo.test')->firstOrFail();
        $bothAccount = ClientAccount::where('code', 'SC-DEMO-BOTH')->firstOrFail();
        $accountingAccount = ClientAccount::where('code', 'SC-DEMO-ACCOUNTING')->firstOrFail();
        $authorization = app(ClientAccountAuthorizationService::class);

        $this->assertFalse(Gate::forUser($viewer)->allows('manageUsers', $bothAccount));
        $this->assertTrue($authorization->userCan($viewer, $bothAccount, ClientCapability::ShipmentView));

        $this->assertTrue($authorization->userCan($accounting, $accountingAccount, ClientCapability::PaymentView));
        $this->assertFalse($authorization->userCan($accounting, $accountingAccount, ClientCapability::OrderCreate));
        $this->assertSame(ClientAccountRole::Accounting, ClientAccountUser::where('user_id', $accounting->id)->firstOrFail()->role);
    }

    private function demoCounts(): array
    {
        return [
            'users' => User::whereIn('email', [
                'noaccount@demo.test',
                'owner@abc-demo.test',
                'multi@demo.test',
                'sender@sender-demo.test',
                'receiver@receiver-demo.test',
                'viewer@abc-demo.test',
                'accounting@abc-demo.test',
            ])->count(),
            'accounts' => ClientAccount::where('code', 'like', 'SC-DEMO-%')->count(),
            'memberships' => ClientAccountUser::count(),
            'customer_links' => ClientAccountCustomer::count(),
            'capabilities' => ClientAccountCapability::count(),
        ];
    }
}