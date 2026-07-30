<?php

namespace Tests\Feature\Onboarding;

use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InvitationActivationTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'opaque-token-123';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('sisahygo.api.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
        app()->forgetInstance(SisahygoApiConfiguration::class);
    }

    public function test_valid_invitation_page_loads(): void
    {
        $this->fakePreview();

        $this->get(route('invitation.show', $this->token))
            ->assertOk()
            ->assertSee('Acme Logistics')
            ->assertSee('contact@example.com');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === $this->coreUrl($this->token)
            && ! $request->hasHeader('X-Api-Key'));
    }

    public function test_invalid_invitation_is_rejected(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'NOT_FOUND', 'message' => 'missing']], 404)]);

        $this->get(route('invitation.show', 'bad-token'))
            ->assertOk()
            ->assertSee(__('onboarding.invitation.unavailable_title'));
    }

    public function test_expired_invitation_is_rejected(): void
    {
        $this->fakePreview(['status' => 'expired']);

        $this->get(route('invitation.show', $this->token))
            ->assertOk()
            ->assertSee(__('onboarding.invitation.errors.expired'));
    }

    public function test_revoked_invitation_is_rejected(): void
    {
        $this->fakePreview(['status' => 'revoked']);

        $this->get(route('invitation.show', $this->token))
            ->assertOk()
            ->assertSee(__('onboarding.invitation.errors.revoked'));
    }

    public function test_already_used_invitation_cannot_create_duplicate_user(): void
    {
        User::factory()->create(['email' => 'contact@example.com']);
        $this->fakePreview(['status' => 'used']);

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertSessionHasErrors('invitation');

        $this->assertSame(1, User::query()->where('email', 'contact@example.com')->count());
        $this->assertSame(0, ClientAccountUser::query()->count());
    }

    public function test_password_validation_works(): void
    {
        $this->fakePreview();

        $this->post(route('invitation.activate', $this->token), [
            'email' => 'contact@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertSame(0, User::query()->count());
    }

    public function test_email_cannot_be_changed(): void
    {
        $this->fakePreview();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload([
            'email' => 'other@example.com',
        ]))->assertSessionHasErrors('email');

        $this->assertSame(0, User::query()->count());
    }

    public function test_new_user_is_created_after_successful_core_activation(): void
    {
        $this->fakePreviewAndActivation();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertRedirect(route('onboarding.welcome'));

        $user = User::query()->where('email', 'contact@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_user_is_reused_safely_without_password_reset(): void
    {
        $existing = User::factory()->create([
            'email' => 'contact@example.com',
            'name' => 'Existing Name',
            'password' => Hash::make('original-password'),
        ]);
        $originalHash = $existing->password;
        $this->fakePreviewAndActivation();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertRedirect(route('onboarding.welcome'));

        $fresh = $existing->fresh();
        $this->assertSame(1, User::query()->where('email', 'contact@example.com')->count());
        $this->assertSame('Existing Name', $fresh->name);
        $this->assertSame($originalHash, $fresh->password);
        $this->assertTrue(Hash::check('original-password', $fresh->password));
        $this->assertFalse(Hash::check('password123', $fresh->password));

        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => 'contact@example.com', 'password' => 'original-password']));
    }

    public function test_client_account_membership_is_created_once(): void
    {
        $this->fakePreviewAndActivation(alreadyActivated: true);

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload());
        $this->post(route('invitation.activate', $this->token), $this->passwordPayload());

        $this->assertSame(1, ClientAccount::query()->where('code', 'ACME')->count());
        $this->assertSame(1, ClientAccountUser::query()->count());
        $this->assertSame(1, ClientAccountCustomer::query()->where('customer_id', 1649051)->count());
    }

    public function test_activation_accepts_empty_capabilities_and_null_credential(): void
    {
        $this->fakePreviewAndActivation();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertRedirect(route('onboarding.welcome'));

        $this->assertSame(1, ClientAccountCapability::query()->count());
        $this->assertSame(1, ClientAccountCapability::query()
            ->where('capability', ClientCapability::SettingsManage->value)
            ->where('is_enabled', true)
            ->count());
        $this->assertSame(1, ClientAccountCustomer::query()->where('customer_id', 1649051)->count());
    }

    public function test_repeated_submission_is_idempotent_when_core_returns_already_activated(): void
    {
        $existing = User::factory()->create([
            'email' => 'contact@example.com',
            'password' => Hash::make('original-password'),
        ]);
        $originalHash = $existing->password;
        $this->fakePreviewAndActivation(alreadyActivated: true, activationOverrides: [
            'capabilities' => ['shipment.view'],
        ]);

        $first = $this->post(route('invitation.activate', $this->token), $this->passwordPayload());
        $second = $this->post(route('invitation.activate', $this->token), $this->passwordPayload());

        $first->assertRedirect(route('onboarding.welcome'));
        $second->assertRedirect(route('dashboard'));
        $this->assertSame(1, User::query()->where('email', 'contact@example.com')->count());
        $this->assertSame($originalHash, $existing->fresh()->password);
        $this->assertSame(1, ClientAccount::query()->where('code', 'ACME')->count());
        $this->assertSame(1, ClientAccountUser::query()->count());
        $this->assertSame(1, ClientAccountCustomer::query()->where('customer_id', 1649051)->count());
        $this->assertSame(1, ClientAccountCapability::query()->where('capability', 'shipment.view')->count());
    }

    public function test_missing_activation_contract_fields_are_rejected_without_local_data(): void
    {
        Http::fake([
            $this->coreUrl($this->token) => Http::response(['data' => $this->previewData()], 200),
            $this->coreUrl($this->token).'/activate' => Http::response(['data' => [
                'invitation_reference' => 'CINV-20260729-ABCDEFGH',
                'activation_status' => 'activated',
                'user' => ['email' => 'contact@example.com', 'role' => 'owner'],
                'client_account' => ['code' => 'ACME', 'name' => 'Acme Logistics'],
                'customer_mappings' => [['customer_id' => 1649051, 'role' => 'both']],
                'credential' => null,
            ]], 200),
        ]);

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertOk()
            ->assertSee(__('onboarding.errors.malformed'));

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, ClientAccount::query()->count());
    }

    public function test_core_failure_does_not_leave_partial_local_data(): void
    {
        Http::fake([
            $this->coreUrl($this->token) => Http::response(['data' => $this->previewData()], 200),
            $this->coreUrl($this->token).'/activate' => Http::response(['error' => ['code' => 'SERVER_ERROR']], 500),
        ]);

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertOk()
            ->assertSee(__('onboarding.errors.server'));

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, ClientAccount::query()->count());
    }

    public function test_raw_invitation_token_is_not_written_to_logs(): void
    {
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('info')->withArgs(function (string $message, array $context): bool {
            return ! str_contains((string) json_encode($context), $this->token)
                && ($context['endpoint'] ?? null) === '/connect-onboarding/invitations/{token}';
        })->atLeast()->once();
        $this->fakePreview();

        $this->get(route('invitation.show', $this->token))->assertOk();
    }

    public function test_existing_multi_account_user_activation_selects_new_client_account_for_setup(): void
    {
        $existing = User::factory()->create([
            'email' => 'contact@example.com',
            'onboarding_welcomed_at' => null,
        ]);
        $accountA = ClientAccount::factory()->active()->create(['code' => 'ALPHA', 'name' => 'Alpha Logistics']);
        ClientAccountUser::factory()->for($accountA)->for($existing)->owner()->create();

        $this->fakePreviewAndActivation(activationOverrides: [
            'client_account' => [
                'external_id' => 'BETA',
                'code' => 'BETA',
                'name' => 'Beta Logistics',
                'status' => 'active',
            ],
            'customer_mappings' => [[
                'customer_id' => 20002,
                'core_customer_id' => 20002,
                'customer_external_id' => '20002',
                'role' => 'both',
            ]],
        ]);

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertRedirect(route('onboarding.welcome'))
            ->assertSessionHas(CurrentClientAccountResolver::SESSION_KEY, fn ($id) => ClientAccount::query()->whereKey($id)->where('code', 'BETA')->exists());

        $accountB = ClientAccount::query()->where('code', 'BETA')->firstOrFail();
        $this->assertSame($accountB->id, session(CurrentClientAccountResolver::SESSION_KEY));

        $this->get(route('onboarding.welcome'))
            ->assertOk()
            ->assertSee('Beta Logistics')
            ->assertDontSee('Alpha Logistics');

        $this->get(route('settings.client-account'))
            ->assertOk()
            ->assertSee('Beta Logistics')
            ->assertSee('Sisahygo API Key')
            ->assertSee('wire:model.defer="apiKey"', false)
            ->assertDontSee('Alpha Logistics');
    }

    public function test_successful_activation_authenticates_user(): void
    {
        $this->fakePreviewAndActivation();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload());

        $this->assertAuthenticated();
    }

    public function test_first_login_welcome_is_shown_once(): void
    {
        $this->fakePreviewAndActivation();
        $this->post(route('invitation.activate', $this->token), $this->passwordPayload());

        $this->get('/welcome')->assertOk()->assertSee(__('onboarding.welcome.title'));
        $this->post('/welcome/start')->assertRedirect(route('client-accounts.select'));
        $this->get('/welcome')->assertRedirect(route('client-accounts.select'));
    }

    public function test_subsequent_login_goes_to_normal_workspace(): void
    {
        $this->fakePreviewAndActivation();
        $this->post(route('invitation.activate', $this->token), $this->passwordPayload());
        $user = auth()->user();
        $user->forceFill(['onboarding_welcomed_at' => now()])->save();

        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_thai_and_english_invitation_pages_render(): void
    {
        $this->fakePreview();
        app()->setLocale('th');
        $this->get(route('invitation.show', $this->token))->assertOk()->assertSee('เริ่มต้นใช้งาน');

        $this->fakePreview();
        app()->setLocale('en');
        $this->get(route('invitation.show', $this->token))->assertOk()->assertSee('Start using Sisahygo Connect');
    }

    public function test_session_is_regenerated_after_successful_activation(): void
    {
        $this->fakePreviewAndActivation();
        $this->startSession();
        $before = session()->getId();

        $this->post(route('invitation.activate', $this->token), $this->passwordPayload())
            ->assertRedirect(route('onboarding.welcome'));

        $this->assertNotSame($before, session()->getId());
    }

    private function fakePreview(array $overrides = []): void
    {
        Http::fake([$this->coreUrl('*') => Http::response(['data' => $this->previewData($overrides)], 200)]);
    }

    private function fakePreviewAndActivation(bool $alreadyActivated = false, array $activationOverrides = []): void
    {
        Http::fake([
            $this->coreUrl($this->token) => Http::response(['data' => $this->previewData()], 200),
            $this->coreUrl($this->token).'/activate' => Http::response(['data' => $this->activationData($alreadyActivated, $activationOverrides)], 200),
        ]);
    }

    /** @return array<string, mixed> */
    private function previewData(array $overrides = []): array
    {
        return array_replace([
            'status' => 'valid',
            'email' => 'contact@example.com',
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'role' => 'owner',
            'email_verified_by_invitation' => true,
            'expires_at' => now()->addDay()->toIso8601String(),
            'client_account' => [
                'code' => 'ACME',
                'name' => 'Acme Logistics',
            ],
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function activationData(bool $alreadyActivated = false, array $overrides = []): array
    {
        return array_replace([
            'invitation_reference' => 'CINV-20260729-ABCDEFGH',
            'access_request_reference' => 'CAR-20260729-9ZI0TB0B',
            'activation_status' => $alreadyActivated ? 'already_activated' : 'activated',
            'already_activated' => $alreadyActivated,
            'email' => 'contact@example.com',
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'role' => 'both',
            'user' => [
                'email' => 'contact@example.com',
                'role' => 'owner',
                'email_verified_by_invitation' => true,
            ],
            'client_account' => [
                'external_id' => 'ACME',
                'code' => 'ACME',
                'name' => 'Acme Logistics',
                'status' => 'active',
            ],
            'customer_mappings' => [[
                'customer_id' => 1649051,
                'core_customer_id' => 1649051,
                'customer_external_id' => '1649051',
                'role' => 'both',
            ]],
            'capabilities' => [],
            'credential' => null,
        ], $overrides);
    }

    /** @return array<string, string> */
    private function passwordPayload(array $overrides = []): array
    {
        return array_replace([
            'email' => 'contact@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function coreUrl(string $token): string
    {
        return 'https://sandbox-api.sisahygo.online/api/v1/connect-onboarding/invitations/'.$token;
    }
}
