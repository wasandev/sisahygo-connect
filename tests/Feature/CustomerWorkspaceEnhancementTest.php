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
use App\Livewire\Orders\OrderShow;
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

    public function test_universal_search_uses_selected_client_account_credentials(): void
    {
        $user = User::factory()->create();
        $other = $this->accountFor($user, 'Other Account', 'other-secret');
        $selected = $this->accountFor($user, 'Selected Account', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selected->id]);

        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
            if ($request->hasHeader('X-Api-Key', 'selected-secret')) {
                return Http::response([
                    'data' => [['tracking_no' => 'SELECTED-1', 'client_reference_no' => 'REF-SAME', 'order_header_no' => 'OH-SELECTED']],
                    'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
                ]);
            }

            return Http::response([
                'data' => [['tracking_no' => 'OTHER-1', 'client_reference_no' => 'REF-SAME', 'order_header_no' => 'OH-OTHER']],
                'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]);
        }]);

        Livewire::test(UniversalSearch::class)
            ->set('query', 'REF-SAME')
            ->call('submit')
            ->assertRedirect(route('orders.show', 'SELECTED-1'));

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
        $this->assertTrue($other->exists);
    }

    public function test_order_detail_identifier_cannot_bypass_selected_account_scope(): void
    {
        $user = User::factory()->create();
        $other = $this->accountFor($user, 'Other Account', 'other-secret');
        $selected = $this->accountFor($user, 'Selected Account', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selected->id]);

        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments/OTHER-1' => function ($request) {
            if ($request->hasHeader('X-Api-Key', 'selected-secret')) {
                return Http::response($this->notFoundFixture(), 404);
            }

            return Http::response(['data' => ['tracking_no' => 'OTHER-1', 'order_header_no' => 'OH-OTHER', 'customer_rec' => 'Other Receiver']]);
        }]);

        Livewire::test(OrderShow::class, ['trackingIdentifier' => 'OTHER-1'])
            ->assertSet('notFound', true)
            ->assertDontSee('Other Receiver')
            ->assertDontSee('other-secret');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'selected-secret'));
        Http::assertNotSent(fn ($request) => $request->hasHeader('X-Api-Key', 'other-secret'));
        $this->assertTrue($other->exists);
    }

    public function test_changing_selected_client_account_changes_universal_search_result(): void
    {
        $user = User::factory()->create();
        $first = $this->accountFor($user, 'First Account', 'first-secret');
        $second = $this->accountFor($user, 'Second Account', 'second-secret');

        Http::fake(['https://sandbox-api.sisahygo.online/api/v1/client/shipments*' => function ($request) {
            $tracking = $request->hasHeader('X-Api-Key', 'second-secret') ? 'SECOND-1' : 'FIRST-1';

            return Http::response([
                'data' => [['tracking_no' => $tracking, 'client_reference_no' => 'REF-SWITCH', 'order_header_no' => 'OH-'.$tracking]],
                'meta' => ['current_page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]);
        }]);

        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $first->id]);
        Livewire::test(UniversalSearch::class)->set('query', 'REF-SWITCH')->call('submit')->assertRedirect(route('orders.show', 'FIRST-1'));

        app()->forgetInstance(ClientAccount::class);
        $this->withSession([CurrentClientAccountResolver::SESSION_KEY => $second->id]);
        Livewire::test(UniversalSearch::class)->set('query', 'REF-SWITCH')->call('submit')->assertRedirect(route('orders.show', 'SECOND-1'));
    }

    public function test_api_failure_does_not_show_cross_account_or_stale_search_result(): void
    {
        $user = User::factory()->create();
        $selected = $this->accountFor($user, 'Selected Account', 'selected-secret');
        $this->actingAs($user)->withSession([CurrentClientAccountResolver::SESSION_KEY => $selected->id]);
        Http::fake(['*' => Http::response(['error' => ['message' => 'server unavailable']], 500)]);

        Livewire::test(UniversalSearch::class)
            ->set('query', 'REF-ANY')
            ->call('submit')
            ->assertNoRedirect()
            ->assertSee('ยังไม่สามารถค้นหาได้ในขณะนี้')
            ->assertDontSee('OH-OTHER')
            ->assertDontSee('other-secret');
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

    private function accountFor(User $user, string $name, string $apiKey): ClientAccount
    {
        $account = ClientAccount::factory()->create(['name' => $name]);
        ClientAccountUser::factory()->for($account)->for($user)->owner()->create(['role' => ClientAccountRole::Owner]);
        ClientAccountCapability::factory()->for($account)->capability(ClientCapability::ShipmentView)->create();
        ClientAccountCustomer::factory()->for($account)->sender()->create(['customer_id' => 10001]);
        app(SisahygoApiCredentialService::class)->create($account, SisahygoApiEnvironment::Sandbox, 'Sandbox', $apiKey);

        return $account;
    }

    private function notFoundFixture(): array
    {
        return ['error' => ['code' => 'not_found', 'message' => 'Not found']];
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function eligibleAccount(): array
    {
        $user = User::factory()->create();

        return [$user, $this->accountFor($user, 'Selected Account', 'secret-api-key')];
    }
}
