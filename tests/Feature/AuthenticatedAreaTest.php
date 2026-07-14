<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedAreaTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('tenantRoutes')]
    public function test_guests_are_redirected_from_authenticated_routes(string $routeName, string $label): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('tenantRoutes')]
    public function test_authenticated_users_can_access_application_routes_with_valid_client_account(string $routeName, string $label): void
    {
        $user = User::factory()->create();
        $this->createClientAccountFor($user);

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($label)
            ->assertSee('Sisahygo Connect');
    }

    #[DataProvider('tenantRoutes')]
    public function test_tenant_routes_require_valid_selected_account_context(string $routeName, string $label): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertForbidden()
            ->assertSee('ยังไม่มีบัญชีลูกค้าที่พร้อมใช้งาน');
    }

    public function test_profile_remains_available_without_selected_client_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('โปรไฟล์');
    }

    public static function tenantRoutes(): array
    {
        return [
            'dashboard' => ['dashboard', 'หน้าหลัก'],
            'order checking' => ['order-checking', 'ตรวจสอบรายการส่งสินค้า'],
            'shipments' => ['shipments', 'การขนส่ง'],
            'tracking' => ['tracking', 'ติดตามสถานะสินค้า'],
            'history' => ['history', 'ประวัติการขนส่ง'],
            'payments' => ['payments', 'การชำระเงิน'],
            'reports' => ['reports', 'รายงาน'],
            'settings' => ['settings', 'ตั้งค่า'],
        ];
    }

    private function createClientAccountFor(User $user): ClientAccount
    {
        $account = ClientAccount::create([
            'name' => 'ABC Company',
            'code' => 'ABC',
        ]);

        ClientAccountUser::create([
            'client_account_id' => $account->id,
            'user_id' => $user->id,
            'role' => ClientAccountRole::Owner,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $account;
    }
}
