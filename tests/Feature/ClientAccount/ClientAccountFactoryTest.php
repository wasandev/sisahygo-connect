<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAccountFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_account_factory_status_states(): void
    {
        $active = ClientAccount::factory()->active()->create();
        $inactive = ClientAccount::factory()->inactive()->create();

        $this->assertSame(ClientAccountStatus::Active, $active->status);
        $this->assertSame(ClientAccountStatus::Suspended, $inactive->status);
    }

    public function test_membership_factory_role_states(): void
    {
        $this->assertSame(ClientAccountRole::Owner, ClientAccountUser::factory()->owner()->create()->role);
        $this->assertSame(ClientAccountRole::Administrator, ClientAccountUser::factory()->administrator()->create()->role);
        $this->assertSame(ClientAccountRole::Operator, ClientAccountUser::factory()->operator()->create()->role);
        $this->assertSame(ClientAccountRole::Viewer, ClientAccountUser::factory()->viewer()->create()->role);
        $this->assertSame(ClientAccountRole::Accounting, ClientAccountUser::factory()->accounting()->create()->role);
    }

    public function test_customer_link_factory_sender_receiver_states(): void
    {
        $sender = ClientAccountCustomer::factory()->sender()->withPaymentAccess()->create();
        $receiver = ClientAccountCustomer::factory()->receiver()->create();
        $both = ClientAccountCustomer::factory()->senderAndReceiver()->create();

        $this->assertTrue($sender->can_send);
        $this->assertFalse($sender->can_receive);
        $this->assertTrue($sender->can_view_payment);

        $this->assertFalse($receiver->can_send);
        $this->assertTrue($receiver->can_receive);

        $this->assertTrue($both->can_send);
        $this->assertTrue($both->can_receive);
    }

    public function test_capability_factory_uses_domain_capability_enum(): void
    {
        $capability = ClientAccountCapability::factory()
            ->capability(ClientCapability::PaymentView)
            ->create();

        $this->assertSame(ClientCapability::PaymentView, $capability->capability);
        $this->assertTrue($capability->is_enabled);
    }
}