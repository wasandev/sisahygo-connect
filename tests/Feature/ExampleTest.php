<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_welcome_page_is_available_to_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sisahygo Connect')
            ->assertSee('Login')
            ->assertSee(route('login'));
    }

    public function test_new_authenticated_users_are_sent_to_first_login_welcome_from_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/welcome');
    }

    public function test_welcomed_authenticated_users_are_sent_to_dashboard_from_home(): void
    {
        $user = User::factory()->create(['onboarding_welcomed_at' => now()]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
