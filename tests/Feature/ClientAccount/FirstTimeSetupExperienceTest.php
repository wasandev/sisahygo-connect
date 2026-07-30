<?php

namespace Tests\Feature\ClientAccount;

use App\Application\Settings\ResolveClientAccountSetupState;
use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Enums\SisahygoCredentialStatus;
use App\Domain\Sisahygo\Models\SisahygoApiCredential;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FirstTimeSetupExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
        config()->set('app.locale', 'en');
    }

    public function test_setup_state_is_incomplete_without_credential(): void
    {
        [$user, $account] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);

        $state = app(ResolveClientAccountSetupState::class)($user, $account);

        $this->assertFalse($state->isReady());
        $this->assertSame(4, $state->completedSteps());
        $this->assertSame('credential_configured', $state->nextActionKey);
        $this->assertTrue($state->canManageSettings);
    }

    public function test_setup_state_is_ready_with_active_verified_credential(): void
    {
        [$user, $account] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'ready-secret-key');
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);

        $state = app(ResolveClientAccountSetupState::class)($user, $account);

        $this->assertTrue($state->isReady());
        $this->assertSame(6, $state->completedSteps());
        $this->assertNull($state->nextActionKey);
    }

    public function test_revoked_credential_does_not_make_setup_ready(): void
    {
        [$user, $account] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);
        $credential = app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'revoked-secret-key');
        $credential->forceFill(['status' => SisahygoCredentialStatus::Revoked, 'active_slot' => null, 'revoked_at' => now()])->save();

        $state = app(ResolveClientAccountSetupState::class)($user, $account);

        $this->assertFalse($state->isReady());
        $this->assertSame('credential_configured', $state->nextActionKey);
    }

    public function test_selected_account_scope_recalculates_setup_state(): void
    {
        [$user, $ready] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);
        app(SisahygoApiCredentialService::class)->create($ready, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'ready-secret-key');
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/ping' => Http::response(['data' => ['status' => 'ok']])]);
        $incomplete = ClientAccount::factory()->active()->create(['name' => 'Incomplete']);
        ClientAccountUser::factory()->for($incomplete)->for($user)->owner()->create();
        ClientAccountCapability::factory()->for($incomplete)->capability(ClientCapability::SettingsManage)->create();
        ClientAccountCustomer::factory()->for($incomplete)->senderAndReceiver()->create();

        $resolver = app(CurrentClientAccountResolver::class);
        $readyState = app(ResolveClientAccountSetupState::class)($user, $resolver->resolve($user, $ready->id)->clientAccount);
        $incompleteState = app(ResolveClientAccountSetupState::class)($user, $resolver->resolve($user, $incomplete->id)->clientAccount);

        $this->assertTrue($readyState->isReady());
        $this->assertFalse($incompleteState->isReady());
        $this->assertSame('credential_configured', $incompleteState->nextActionKey);
    }

    #[DataProvider('failedPingProvider')]
    public function test_failed_ping_keeps_setup_incomplete_and_dashboard_banner_visible(string $mode): void
    {
        [$user, $account] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'active-but-unverified-key');
        $this->fakePingFailure($mode);

        $state = app(ResolveClientAccountSetupState::class)($user, $account);

        $this->assertFalse($state->isReady());
        $this->assertSame('api_connected', $state->nextActionKey);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Set up Sisahygo API Credential')
            ->assertSee('Set up credential');
    }

    public static function failedPingProvider(): array
    {
        return [
            '401 invalid' => ['401'],
            '403 inactive' => ['403'],
            '429 rate limited' => ['429'],
            'timeout' => ['timeout'],
            'malformed' => ['malformed'],
            'offline' => ['offline'],
        ];
    }

    public function test_first_login_welcome_shows_owner_action_and_member_guidance(): void
    {
        [$owner] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);

        $this->actingAs($owner)
            ->get(route('onboarding.welcome'))
            ->assertOk()
            ->assertSee('Welcome to Sisahygo Connect')
            ->assertSee('Set up credential');

        [$member] = $this->accountWithFoundation(ClientAccountRole::Viewer, settingsManage: true);

        $this->actingAs($member)
            ->get(route('onboarding.welcome'))
            ->assertOk()
            ->assertSee('account administrator must complete')
            ->assertDontSee('Set up credential');
    }

    public function test_dashboard_shows_incomplete_setup_banner_for_selected_account(): void
    {
        [$user, $account] = $this->accountWithFoundation(ClientAccountRole::Owner, settingsManage: true);

        $this->actingAs($user)
            ->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Set up Sisahygo API Credential')
            ->assertSee('Set up credential');
    }

    private function fakePingFailure(string $mode): void
    {
        Http::fake(function () use ($mode) {
            return match ($mode) {
                '401' => Http::response(['error' => ['code' => 'API_KEY_INVALID']], 401),
                '403' => Http::response(['error' => ['code' => 'API_CLIENT_INACTIVE']], 403),
                '429' => Http::response(['error' => ['code' => 'RATE_LIMITED']], 429),
                'timeout' => throw new ConnectionException('timed out'),
                'malformed' => Http::response('not-json', 200, ['Content-Type' => 'application/json']),
                'offline' => throw new ConnectionException('Network is unreachable'),
            };
        });
    }

    private function accountWithFoundation(ClientAccountRole $role, bool $settingsManage): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->active()->create();

        ClientAccountUser::factory()->for($account)->for($user)->create(['role' => $role]);
        ClientAccountCustomer::factory()->for($account)->senderAndReceiver()->create();

        if ($settingsManage) {
            ClientAccountCapability::factory()->for($account)->capability(ClientCapability::SettingsManage)->create();
        }

        return [$user, $account];
    }
}
