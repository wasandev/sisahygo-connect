<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedAreaTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('authenticatedRoutes')]
    public function test_guests_are_redirected_from_authenticated_routes(string $routeName, string $label): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('authenticatedRoutes')]
    public function test_authenticated_users_can_access_application_routes(string $routeName, string $label): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($label)
            ->assertSee('Sisahygo Connect');
    }

    public static function authenticatedRoutes(): array
    {
        return [
            'dashboard' => ['dashboard', 'Dashboard'],
            'order checking' => ['order-checking', 'Order Checking'],
            'shipments' => ['shipments', 'Shipments'],
            'tracking' => ['tracking', 'Tracking'],
            'history' => ['history', 'History'],
            'payments' => ['payments', 'Payments'],
            'reports' => ['reports', 'Reports'],
            'settings' => ['settings', 'Settings'],
        ];
    }
}