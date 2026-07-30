<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientAccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_client_account_settings(): void
    {
        $this->get(route('settings.client-account'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_client_account_foundation_settings(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::create(['name' => 'ABC Company', 'code' => 'ABC']);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        ClientAccountCustomer::create([
            'client_account_id' => $account->id,
            'customer_id' => 1001,
            'can_send' => true,
            'can_receive' => true,
            'can_view_payment' => true,
            'is_active' => true,
        ]);

        ClientAccountCapability::create([
            'client_account_id' => $account->id,
            'capability' => ClientCapability::SettingsManage,
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('บัญชีลูกค้า')
            ->assertSee('ABC Company')
            ->assertSee('settings.manage');
    }

    public function test_authenticated_user_without_client_account_sees_safe_unavailable_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.client-account'))
            ->assertForbidden()
            ->assertSee('ยังไม่มีบัญชีลูกค้าที่พร้อมใช้งาน')
            ->assertDontSee('Sisahygo API Key')
            ->assertDontSee('wire:model.defer="apiKey"', false);
    }

    public function test_api_status_card_reports_connected_without_exposing_secret(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'ABC Company', 'code' => 'ABC']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('สถานะการเชื่อมต่อ Sisahygo API')
            ->assertSee('เชื่อมต่อได้')
            ->assertDontSee('secret-api-key')
            ->assertDontSee('X-Api-Key');

        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/ping'
            && $request->hasHeader('X-Api-Key', 'secret-api-key'));
    }

    public function test_api_status_card_handles_missing_credential_safely(): void
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'ABC Company', 'code' => 'ABC']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        Http::fake();

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('ไม่มี Credential')
            ->assertSee('ยังไม่มี Sisahygo API Credential')
            ->assertDontSee('secret-api-key');

        Http::assertNothingSent();
    }
}
