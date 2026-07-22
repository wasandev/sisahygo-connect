<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Livewire\Shipments\ShipmentIndex;
use App\Livewire\Shipments\ShipmentShow;
use App\Livewire\Shipments\TrackingLookup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipments_page_renders_for_eligible_account(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeShipmentList();

        $this->get(route('shipments'))
            ->assertOk()
            ->assertSee('รายการขนส่ง')
            ->assertSee('OH10001')
            ->assertDontSee('secret-api-key');
    }

    public function test_index_search_uses_selected_active_account_from_session(): void
    {
        $user = User::factory()->create();
        $otherAccount = $this->accountFor($user, 'Other Account', 'other-secret');
        $selectedAccount = $this->accountFor($user, 'Selected Account', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selectedAccount->id]);
        $this->fakeShipmentList();

        Livewire::test(ShipmentIndex::class)
            ->set('keyword', '12345')
            ->call('search')
            ->assertSet('pageError', null)
            ->assertSee('OH10001');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
    }

    public function test_date_validation_error_is_ready_for_ui(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeShipmentList();

        Livewire::test(ShipmentIndex::class)
            ->set('dateFrom', '2026-07-17')
            ->set('dateTo', '2026-07-01')
            ->call('search')
            ->assertHasErrors(['dateTo']);
    }

    public function test_detail_page_renders_timeline_and_items(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments/SH10001' => Http::response($this->fixture('shipment-detail-success.json'))]);

        Livewire::test(ShipmentShow::class, ['trackingIdentifier' => 'SH10001'])
            ->assertSet('pageError', null)
            ->assertSee('Fake parcel')
            ->assertSee('รับสินค้าแล้ว')
            ->assertDontSee('secret-api-key');
    }

    public function test_tracking_lookup_redirects_to_detail_page(): void
    {
        Livewire::test(TrackingLookup::class)
            ->set('trackingIdentifier', 'SH10001')
            ->call('submit')
            ->assertRedirect(route('shipments.show', 'SH10001'));
    }

    public function test_tracking_lookup_requires_identifier(): void
    {
        Livewire::test(TrackingLookup::class)
            ->set('trackingIdentifier', '')
            ->call('submit')
            ->assertHasErrors(['trackingIdentifier']);
    }

    private function fakeShipmentList(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => Http::response($this->fixture('shipments-index-success.json'))]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = $this->accountFor($user, 'Selected Account', 'secret-api-key');

        return [$user, $account];
    }

    private function accountFor(User $user, string $name, string $apiKey): ClientAccount
    {
        $account = ClientAccount::factory()->create(['name' => $name]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return $account;
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path("tests/Fixtures/Sisahygo/V1/{$name}"));
    }
}
