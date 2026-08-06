<?php

namespace Tests\Feature\ClientAccount;

use App\Application\ClientAccounts\ProvisionBaselineClientAccountCapabilities;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaselineCapabilityProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_account_receives_exact_business_baseline_capabilities(): void
    {
        $account = ClientAccount::factory()->active()->create(['code' => 'BASELINE']);

        $added = app(ProvisionBaselineClientAccountCapabilities::class)->provision($account);

        $this->assertEqualsCanonicalizing($this->baseline(), $added);
        foreach ($this->baseline() as $capability) {
            $this->assertEnabled($account, $capability);
        }

        $this->assertMissingCapability($account, ClientCapability::SettingsManage);
        $this->assertMissingCapability($account, ClientCapability::UsersManage);
        $this->assertMissingCapability($account, ClientCapability::ShipmentExport);
        $this->assertMissingCapability($account, ClientCapability::PaymentDownload);
    }

    public function test_provisioning_is_idempotent_preserves_custom_capabilities_and_avoids_duplicates(): void
    {
        $account = ClientAccount::factory()->active()->create(['code' => 'PARTIAL']);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::PaymentDownload)->create();

        $provisioner = app(ProvisionBaselineClientAccountCapabilities::class);

        $first = $provisioner->provision($account);
        $second = $provisioner->provision($account->fresh());

        $this->assertEqualsCanonicalizing([
            ClientCapability::OrderCreate,
            ClientCapability::OrderBulk,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
            ClientCapability::ReportView,
            ClientCapability::ReportExport,
        ], $first);
        $this->assertSame([], $second);

        foreach ([...$this->baseline(), ClientCapability::SettingsManage, ClientCapability::PaymentDownload] as $capability) {
            $this->assertSame(1, ClientAccountCapability::query()
                ->where('client_account_id', $account->id)
                ->where('capability', $capability->value)
                ->count());
            $this->assertEnabled($account, $capability);
        }

        $this->assertMissingCapability($account, ClientCapability::UsersManage);
        $this->assertMissingCapability($account, ClientCapability::ShipmentExport);
    }

    public function test_provisioning_does_not_reenable_disabled_capabilities(): void
    {
        $account = ClientAccount::factory()->active()->create(['code' => 'CUSTOM']);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->inactive()->create();

        app(ProvisionBaselineClientAccountCapabilities::class)->provision($account);

        $this->assertSame(1, ClientAccountCapability::query()->where('client_account_id', $account->id)->where('capability', ClientCapability::ShipmentView->value)->count());
        $this->assertDatabaseHas('client_account_capabilities', [
            'client_account_id' => $account->id,
            'capability' => ClientCapability::ShipmentView->value,
            'is_enabled' => false,
        ]);
    }

    public function test_inactive_accounts_are_not_provisioned(): void
    {
        $account = ClientAccount::factory()->inactive()->create(['code' => 'INACTIVE']);

        $this->assertSame([], app(ProvisionBaselineClientAccountCapabilities::class)->provision($account));
        $this->assertSame(0, ClientAccountCapability::query()->where('client_account_id', $account->id)->count());
    }

    public function test_repair_command_dry_run_reports_present_missing_and_to_add_without_mutating_data(): void
    {
        $account = ClientAccount::factory()->active()->create(['code' => 'DRYRUN', 'name' => 'Dry Run Account']);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentHistory)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();

        $this->artisan('client-account:provision-baseline-capabilities', ['account' => 'DRYRUN', '--dry-run' => true])
            ->expectsOutputToContain('Client Account: DRYRUN - Dry Run Account')
            ->expectsOutputToContain('Currently present capabilities')
            ->expectsOutputToContain('settings.manage')
            ->expectsOutputToContain('Missing baseline capabilities')
            ->expectsOutputToContain('Capabilities to add')
            ->expectsOutputToContain('order.bulk')
            ->expectsOutputToContain('payment.view')
            ->expectsOutputToContain('Dry run only. No capabilities were changed.')
            ->assertSuccessful();

        $this->assertSame(4, ClientAccountCapability::query()->where('client_account_id', $account->id)->count());
        $this->assertMissingCapability($account, ClientCapability::OrderBulk);
        $this->assertMissingCapability($account, ClientCapability::PaymentView);
    }

    public function test_repair_command_adds_only_missing_baseline_capabilities_once(): void
    {
        $account = ClientAccount::factory()->active()->create(['code' => 'REPAIR']);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::OrderCreate)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentHistory)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::PaymentDownload)->create();

        $this->artisan('client-account:provision-baseline-capabilities', ['account' => 'REPAIR'])
            ->expectsOutputToContain('order.bulk')
            ->expectsOutputToContain('payment.view')
            ->expectsOutputToContain('Added 4 baseline capabilities.')
            ->assertSuccessful();

        $this->artisan('client-account:provision-baseline-capabilities', ['account' => 'REPAIR'])
            ->expectsOutputToContain('No baseline capabilities are missing.')
            ->assertSuccessful();

        foreach ([...$this->baseline(), ClientCapability::SettingsManage, ClientCapability::PaymentDownload] as $capability) {
            $this->assertSame(1, ClientAccountCapability::query()
                ->where('client_account_id', $account->id)
                ->where('capability', $capability->value)
                ->count());
            $this->assertEnabled($account, $capability);
        }

        $this->assertMissingCapability($account, ClientCapability::UsersManage);
        $this->assertMissingCapability($account, ClientCapability::ShipmentExport);
    }

    public function test_repair_command_rejects_inactive_and_unknown_accounts(): void
    {
        $account = ClientAccount::factory()->create(['code' => 'SUSPENDED', 'status' => ClientAccountStatus::Suspended]);

        $this->artisan('client-account:provision-baseline-capabilities', ['account' => $account->code])
            ->expectsOutputToContain('Client Account must be active before baseline capabilities can be provisioned.')
            ->assertFailed();

        $this->artisan('client-account:provision-baseline-capabilities', ['account' => 'UNKNOWN'])
            ->expectsOutputToContain('Client Account was not found.')
            ->assertFailed();
    }

    /** @return list<ClientCapability> */
    private function baseline(): array
    {
        return [
            ClientCapability::OrderCreate,
            ClientCapability::OrderBulk,
            ClientCapability::ShipmentView,
            ClientCapability::ShipmentHistory,
            ClientCapability::PaymentView,
            ClientCapability::ReportView,
            ClientCapability::ReportExport,
        ];
    }

    private function assertEnabled(ClientAccount $account, ClientCapability $capability): void
    {
        $this->assertDatabaseHas('client_account_capabilities', [
            'client_account_id' => $account->id,
            'capability' => $capability->value,
            'is_enabled' => true,
        ]);
    }

    private function assertMissingCapability(ClientAccount $account, ClientCapability $capability): void
    {
        $this->assertDatabaseMissing('client_account_capabilities', [
            'client_account_id' => $account->id,
            'capability' => $capability->value,
        ]);
    }
}
