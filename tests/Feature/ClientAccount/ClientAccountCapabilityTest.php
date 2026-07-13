<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Services\ClientAccountAuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccountCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_capability_is_detected(): void
    {
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);

        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => ClientCapability::ShipmentView,
            'is_enabled' => true,
        ]);

        $this->assertTrue(app(ClientAccountAuthorizationService::class)->hasCapability($account, ClientCapability::ShipmentView));
    }

    public function test_disabled_capability_is_not_detected(): void
    {
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);

        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => ClientCapability::PaymentView,
            'is_enabled' => false,
        ]);

        $this->assertFalse(app(ClientAccountAuthorizationService::class)->hasCapability($account, ClientCapability::PaymentView));
    }
}