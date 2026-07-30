<?php

namespace Tests\Feature\ClientAccount;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Livewire\Settings\ClientAccount\CredentialSetup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SisahygoCredentialSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
        config()->set('app.locale', 'en');
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
    }

    public function test_owner_with_settings_manage_can_view_credential_setup(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('Sisahygo API')
            ->assertSee('Sisahygo API key')
            ->assertDontSee('secret-api-key');
    }

    public function test_unauthorised_member_cannot_view_or_submit_the_form(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Viewer, settingsManage: true);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('An account owner or administrator')
            ->assertDontSee('Sisahygo API key');

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $this->apiKey('blocked'))
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_valid_key_is_verified_and_saved_encrypted_for_selected_account(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $apiKey = $this->apiKey('valid');
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']]),
        ]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $apiKey)
            ->call('save')
            ->assertSet('apiKey', '')
            ->assertSee('Fingerprint')
            ->assertDontSee($apiKey);

        $credential = SisahygoApiCredential::query()->firstOrFail();
        $this->assertSame($account->id, $credential->client_account_id);
        $this->assertSame(SisahygoApiEnvironment::Sandbox, $credential->environment);
        $this->assertSame(SisahygoCredentialStatus::Active, $credential->status);
        $this->assertSame($apiKey, $credential->apiKey());

        $raw = DB::table('sisahygo_api_credentials')->where('id', $credential->id)->value('encrypted_api_key');
        $this->assertStringNotContainsString($apiKey, $raw);

        $activityMetadata = DB::table('client_account_activity_logs')->pluck('metadata')->implode(' ');
        $this->assertStringNotContainsString($apiKey, $activityMetadata);

        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/ping'
            && $request->hasHeader('X-Api-Key', $apiKey));
    }

    public function test_invalid_key_is_rejected_and_not_stored(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['error' => ['code' => 'API_KEY_INVALID']], 401),
        ]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $this->apiKey('invalid'))
            ->call('save')
            ->assertSee('Core rejected this API key');

        $this->assertDatabaseCount('sisahygo_api_credentials', 0);
    }

    public function test_unavailable_core_returns_safe_error_and_keeps_existing_credential(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $old = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Existing', $this->apiKey('old'));
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $this->apiKey('new'))
            ->call('save')
            ->assertSee('could not be reached');

        $this->assertTrue($old->fresh()->isActive());
        $this->assertSame(1, SisahygoApiCredential::query()->where('client_account_id', $account->id)->where('active_slot', 'active')->count());
    }

    public function test_verified_replacement_revokes_prior_local_credential(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $old = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Existing', $this->apiKey('old'));
        $newKey = $this->apiKey('new');
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $newKey)
            ->call('save')
            ->assertSee('verified and saved')
            ->assertDontSee($newKey);

        $new = app(SisahygoApiCredentialService::class)->resolveActive($account, SisahygoApiEnvironment::Sandbox);
        $this->assertSame(SisahygoCredentialStatus::Revoked, $old->fresh()->status);
        $this->assertSame($old->id, $new->rotated_from_id);
        $this->assertSame($newKey, $new->apiKey());
        $this->assertSame(1, SisahygoApiCredential::query()->where('client_account_id', $account->id)->where('active_slot', 'active')->count());
    }

    public function test_repeated_submission_of_active_key_is_idempotent(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $apiKey = $this->apiKey('repeat');
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Existing', $apiKey);
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $apiKey)
            ->call('save')
            ->assertSee('verified and saved');

        $this->assertSame(1, SisahygoApiCredential::query()->where('client_account_id', $account->id)->count());
        $this->assertSame(1, SisahygoApiCredential::query()->where('client_account_id', $account->id)->where('active_slot', 'active')->count());
    }

    public function test_credential_is_saved_only_for_currently_selected_account(): void
    {
        [$user, $first] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $second = ClientAccount::factory()->active()->create(['code' => 'SECOND']);
        ClientAccountUser::factory()->for($second)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($second)->capability(ClientCapability::SettingsManage)->create();
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $first->id]);
        app()->instance(ClientAccount::class, $first);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $this->apiKey('selected'))
            ->call('save');

        $this->assertSame(1, SisahygoApiCredential::query()->where('client_account_id', $first->id)->count());
        $this->assertSame(0, SisahygoApiCredential::query()->where('client_account_id', $second->id)->count());
    }

    public function test_connectivity_status_succeeds_after_setup(): void
    {
        [$user, $account] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);
        $apiKey = $this->apiKey('status');
        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']]),
            'https://sandbox-api.sisahygo.online/api/v1/client/units' => Http::response(['data' => [['unit_id' => 1, 'unit_name' => 'box']]]),
        ]);

        $this->actingAs($user);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        app()->instance(ClientAccount::class, $account);

        Livewire::test(CredentialSetup::class)
            ->set('apiKey', $apiKey)
            ->call('save');

        $this->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('Connected')
            ->assertDontSee($apiKey);
    }

    public function test_welcome_shows_owner_next_step_and_member_guidance(): void
    {
        [$owner, $ownerAccount] = $this->accountWithUser(ClientAccountRole::Owner, settingsManage: true);

        $this->actingAs($owner)
            ->get(route('onboarding.welcome'))
            ->assertOk()
            ->assertSee('Set up Sisahygo API Credential')
            ->assertSee('Set up credential');

        [$member, $memberAccount] = $this->accountWithUser(ClientAccountRole::Viewer, settingsManage: true);

        $this->actingAs($member)
            ->get(route('onboarding.welcome'))
            ->assertOk()
            ->assertSee('account administrator must complete')
            ->assertDontSee('Set up credential');
    }

    private function accountWithUser(ClientAccountRole $role, bool $settingsManage): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->active()->create();

        ClientAccountUser::factory()->for($account)->for($user)->create(['role' => $role]);

        if ($settingsManage) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        }

        return [$user, $account];
    }

    private function apiKey(string $label): string
    {
        return str_pad(hash('sha256', $label), 64, '0');
    }
}
