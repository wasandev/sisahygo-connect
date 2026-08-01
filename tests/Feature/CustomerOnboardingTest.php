<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use App\Livewire\Onboarding\RequestAccess;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
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
            ->assertSee('ชื่อบริษัท / ชื่อลูกค้า')
            ->assertSee('ส่งคำขอใช้งาน')
            ->assertSee(route('login'), false);
    }

    public function test_request_access_submits_to_core_without_local_access_request(): void
    {
        config()->set('sisahygo.api.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
        app()->forgetInstance(SisahygoApiConfiguration::class);

        Http::fake(['*' => Http::response([
            'data' => [
                'request_no' => 'CAR-20260724-ABCDEFGH',
                'public_id' => 'CAR-20260724-ABCDEFGH',
                'connect_reference' => 'CONNECT-REQ-20260724-ABC123',
                'status' => 'pending',
                'status_label' => 'รออนุมัติ',
                'submitted_at' => '2026-07-24T10:00:00+07:00',
            ],
            'meta' => ['duplicate' => false],
        ], 201)]);

        Livewire::test(RequestAccess::class)
            ->set('company_name', 'Acme Logistics')
            ->set('contact_name', 'Anong Contact')
            ->set('email', 'contact@example.com')
            ->set('phone', '0812345678')
            ->set('province', 'Bangkok')
            ->set('website', 'https://example.com')
            ->set('number_of_branches', 3)
            ->set('additional_notes', 'Need sandbox onboarding.')
            ->call('submit')
            ->assertSet('state', 'success')
            ->assertSee('CAR-20260724-ABCDEFGH');

        $this->assertDatabaseCount('access_requests', 0);
        Http::assertSent(fn ($request) => $request->url() === 'https://sandbox-api.sisahygo.online/api/v1/client/access-requests'
            && ! $request->hasHeader('X-Api-Key'));
    }

    public function test_invitation_activation_uses_core_contract_instead_of_local_access_request(): void
    {
        config()->set('sisahygo.api.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');
        app()->forgetInstance(SisahygoApiConfiguration::class);

        Http::fake([
            'https://sandbox-api.sisahygo.online/api/v1/connect-onboarding/invitations/fake-token-123' => Http::response([
                'data' => [
                    'status' => 'valid',
                    'email' => 'contact@example.com',
                    'company_name' => 'Acme Logistics',
                    'contact_name' => 'Anong Contact',
                    'role' => 'owner',
                    'email_verified_by_invitation' => true,
                    'client_account' => ['code' => 'ACME', 'name' => 'Acme Logistics'],
                ],
            ]),
            'https://sandbox-api.sisahygo.online/api/v1/connect-onboarding/invitations/fake-token-123/activate' => Http::response([
                'data' => [
                    'invitation_reference' => 'CINV-20260729-ABCDEFGH',
                    'access_request_reference' => 'CAR-20260729-9ZI0TB0B',
                    'activation_status' => 'activated',
                    'already_activated' => false,
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
                ],
            ]),
        ]);

        $this->get('/invitation/fake-token-123')
            ->assertOk()
            ->assertSee('Acme Logistics')
            ->assertSee('contact@example.com');

        $this->post('/invitation/fake-token-123', [
            'email' => 'contact@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/welcome');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'contact@example.com']);
        $this->assertDatabaseHas('client_accounts', ['code' => 'ACME', 'name' => 'Acme Logistics']);
        $this->assertDatabaseHas('client_account_users', ['role' => 'owner', 'is_active' => true]);
        $this->assertDatabaseHas('client_account_customers', [
            'customer_id' => 1649051,
            'can_send' => true,
            'can_receive' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseCount('access_requests', 0);
    }

    public function test_first_login_welcome_sets_flag_and_continues_to_account_selection(): void
    {
        $user = User::factory()->create(['onboarding_welcomed_at' => null]);
        $account = ClientAccount::factory()->active()->create();
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create();

        $this->actingAs($user)
            ->get('/welcome')
            ->assertOk()
            ->assertSee('ยินดีต้อนรับสู่ Sisahygo Connect')
            ->assertSee('สร้างรายการขนส่ง')
            ->assertSee('ติดตามสถานะขนส่ง')
            ->assertSee('ศูนย์การชำระเงิน')
            ->assertSee('ประวัติรายการ');

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
