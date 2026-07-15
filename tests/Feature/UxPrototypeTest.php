<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UxPrototypeTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('prototypeRoutes')]
    public function test_ux_prototype_routes_render_for_authenticated_client_account(string $routeName, string $text): void
    {
        $user = User::factory()->create();
        $this->createClientAccountFor($user);

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($text)
            ->assertSee('Sisahygo Connect');
    }

    public static function prototypeRoutes(): array
    {
        return [
            'ux dashboard' => ['ux.dashboard', 'ภาพรวมวันนี้'],
            'ux order checking' => ['ux.order-checking', 'ตรวจสอบรายการส่งสินค้า'],
            'ux tracking' => ['ux.tracking', 'ติดตามสถานะสินค้า'],
            'ux shipment detail' => ['ux.shipment-detail', 'SH-240715-001'],
            'ux payments' => ['ux.payments', 'การชำระเงิน'],
            'ux reports' => ['ux.reports', 'รายงาน'],
            'ux settings' => ['ux.settings', 'ตั้งค่า'],
            'ux profile' => ['ux.profile', 'โปรไฟล์ของฉัน'],
            'ux notifications' => ['ux.notifications', 'การแจ้งเตือน'],
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
