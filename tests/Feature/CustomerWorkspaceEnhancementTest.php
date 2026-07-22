<?php

namespace Tests\Feature;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Enums\ClientCapability;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountCapability;
use App\Domain\ClientAccount\Models\ClientAccountCustomer;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Workspace\UniversalSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerWorkspaceEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_universal_search_resolves_client_reference_to_order_detail(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        $this->fakeSearchResponses();

        Livewire::test(UniversalSearch::class)
            ->set('query', 'REF-10001')
            ->call('submit')
            ->assertRedirect(route('orders.show', '10001'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'order_header_no=REF-10001'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'client_reference_no=REF-10001'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'batch_reference_no=REF-10001'));
    }

    public function test_universal_search_shows_safe_not_found_state(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake(['*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 0, 'last_page' => 1]])]);

        Livewire::test(UniversalSearch::class)
            ->set('query', 'MISSING')
            ->call('submit')
            ->assertSee('ไม่พบผลลัพธ์สำหรับ MISSING');
    }

    public function test_notification_center_uses_mock_data_only(): void
    {
        [$user, $account] = $this->eligibleAccount();
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $account->id]);
        Http::fake();

        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('การแจ้งเตือน')
            ->assertSee('Phase 1: Mock Data')
            ->assertSee('OH90001');

        Livewire::test(NotificationCenter::class)
            ->set('filter', 'unread')
            ->assertSee('รายการจัดส่งต้องติดตาม')
            ->assertDontSee('ข้อมูลจาก Sisahygo พร้อมใช้งาน');

        Http::assertNothingSent();
    }

    private function fakeSearchResponses(): void
    {
        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['client_reference_no'] ?? null) === 'REF-10001') {
                return Http::response([
                    'data' => [[
                        'id' => 10001,
                        'tracking_no' => '10001',
                        'client_reference_no' => 'REF-10001',
                        'order_header_no' => 'OH10001',
                        'order_header_date' => '2026-07-16',
                        'order_status' => 'delivered',
                    ]],
                    'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
                ]);
            }

            return Http::response(['data' => [], 'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 0, 'last_page' => 1]]);
        }]);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(): array
    {
        $user = User::factory()->create();
        $account = ClientAccount::factory()->create(['name' => 'Selected Account']);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', 'secret-api-key');

        return [$user, $account];
    }
}
