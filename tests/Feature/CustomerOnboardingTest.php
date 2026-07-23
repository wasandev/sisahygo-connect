<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Onboarding\Models\AccessRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_request_access_page(): void
    {
        $this->get('/request-access')
            ->assertOk()
            ->assertSee('ขอเปิดใช้งาน Sisahygo Connect')
            ->assertSee('Company Name')
            ->assertSee('ส่งคำขอใช้งาน')
            ->assertSee(route('login'), false);
    }

    public function test_request_access_stores_pending_access_request_locally(): void
    {
        $this->post('/request-access', [
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'email' => 'contact@example.com',
            'phone' => '0812345678',
            'province' => 'Bangkok',
            'website' => 'https://example.com',
            'number_of_branches' => 3,
            'additional_notes' => 'Need sandbox onboarding.',
        ])->assertRedirect(route('request-access.success'));

        $this->assertDatabaseHas('access_requests', [
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'email' => 'contact@example.com',
            'status' => AccessRequest::STATUS_PENDING,
        ]);

        $this->assertNotSame('', (string) AccessRequest::query()->first()->invitation_token);
    }

    public function test_mock_invitation_activation_creates_user_client_account_and_logs_in(): void
    {
        $accessRequest = AccessRequest::query()->create([
            'company_name' => 'Acme Logistics',
            'contact_name' => 'Anong Contact',
            'email' => 'contact@example.com',
            'phone' => '0812345678',
            'province' => 'Bangkok',
            'status' => AccessRequest::STATUS_PENDING,
            'invitation_token' => 'fake-token-123',
            'submitted_at' => now(),
        ]);

        $this->get('/invitation/fake-token-123')
            ->assertOk()
            ->assertSee('Welcome')
            ->assertSee('Acme Logistics')
            ->assertSee('เริ่มใช้งาน');

        $this->post('/invitation/fake-token-123', [
            'company_name' => 'Acme Logistics',
            'email' => 'contact@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/welcome');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'contact@example.com']);
        $this->assertDatabaseHas('client_accounts', ['name' => 'Acme Logistics']);
        $this->assertDatabaseHas('client_account_users', ['role' => 'owner', 'is_active' => true]);
        $this->assertSame(AccessRequest::STATUS_APPROVED, $accessRequest->fresh()->status);
    }

    public function test_first_login_welcome_sets_flag_and_continues_to_account_selection(): void
    {
        $user = User::factory()->create(['onboarding_welcomed_at' => null]);
        $account = ClientAccount::factory()->active()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create();

        $this->actingAs($user)
            ->get('/welcome')
            ->assertOk()
            ->assertSee('Welcome to Sisahygo Connect')
            ->assertSee('Create Shipment')
            ->assertSee('Track Shipment')
            ->assertSee('Payment Center')
            ->assertSee('History');

        $this->post('/welcome/start')
            ->assertRedirect(route('client-accounts.select'));

        $this->assertNotNull($user->fresh()->onboarding_welcomed_at);
    }

    public function test_welcomed_user_skips_first_login_welcome(): void
    {
        $user = User::factory()->create(['onboarding_welcomed_at' => now()]);

        $this->actingAs($user)
            ->get('/welcome')
            ->assertRedirect(route('client-accounts.select'));
    }
}
