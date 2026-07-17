<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientAccountStatus;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Support\SisahygoIntegrationContextBuilder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SisahygoIntegrationContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_contains_distinct_sender_and_receiver_scopes(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        ClientAccountCustomer::factory()->for($account)->receiver()->create(['customer_id' => 20001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $context = app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);

        $this->assertSame([10001], $context->authorizedSenderCustomerIds);
        $this->assertSame([20001], $context->authorizedReceiverCustomerIds);
        $this->assertArrayNotHasKey('api_key', $context->safeLogContext());
    }

    public function test_capability_is_required_before_context_is_created(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create();
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $this->expectException(AuthorizationException::class);

        app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);
    }

    public function test_active_account_passes_status_validation(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        $account->update(['status' => ClientAccountStatus::Active]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $context = app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);

        $this->assertSame($account->id, $context->clientAccountId);
    }

    public function test_inactive_account_or_membership_is_rejected(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        $account->update(['status' => ClientAccountStatus::Suspended]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $this->expectException(AuthorizationException::class);

        app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);
    }

    public function test_archived_account_is_rejected(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        $account->update(['status' => ClientAccountStatus::Archived]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $this->expectException(AuthorizationException::class);

        app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);
    }

    public function test_arbitrary_customer_id_is_rejected_by_scope_assertions(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $context = app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);

        $this->expectException(HttpException::class);

        $context->assertSenderCustomerId(99999);
    }

    public function test_sender_only_context_cannot_act_as_receiver(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $context = app(SisahygoIntegrationContextBuilder::class)->build($user, $account, ClientCapability::ShipmentView);

        $this->assertSame([10001], $context->authorizedSenderCustomerIds);
        $this->assertSame([], $context->authorizedReceiverCustomerIds);
    }

    public function test_queue_context_reconstruction_revalidates_credential_account_and_capability(): void
    {
        [$user, $account] = $this->accountWithCapability(ClientCapability::ShipmentView);
        $credential = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-key');

        $context = app(SisahygoIntegrationContextBuilder::class)->rebuildForQueue($user, $account, $credential->id, ClientCapability::ShipmentView, 'queue-correlation');

        $this->assertSame('queue-correlation', $context->correlationId);
        $this->assertSame($credential->id, $context->credentialId);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function accountWithCapability(ClientCapability $capability): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability($capability)->create();

        return [$user, $account];
    }
}
