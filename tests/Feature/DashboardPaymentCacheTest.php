<?php

namespace Tests\Feature;

use App\Application\Dashboard\DashboardPaymentOverviewService;
use App\Application\Payment\PaymentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Exceptions\SisahygoServerException;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DashboardPaymentCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        config()->set('sisahygo.dashboard.payment_cache_enabled', true);
        config()->set('sisahygo.dashboard.payment_cache_ttl', 60);
        Cache::store('array')->clear();
    }

    public function test_cache_miss_calls_payment_query_service_once_and_caches_success(): void
    {
        [$user, $account] = $this->userAndAccount();
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->once()->andReturn($this->paymentResult('1,200.00'));
        });

        $service = app(DashboardPaymentOverviewService::class);
        $first = $service->get($user, $account);
        $second = $service->get($user, $account);

        $this->assertSame('1,200.00', $first['summary']['total_amount_display']);
        $this->assertSame('1,200.00', $second['summary']['total_amount_display']);
        $this->assertSame('hit', $second['cache']['status']);
    }

    public function test_cache_keys_are_isolated_by_client_account_and_contain_no_api_key(): void
    {
        [$user, $first] = $this->userAndAccount();
        $second = ClientAccount::factory()->create(['name' => 'Second Account']);
        $service = app(DashboardPaymentOverviewService::class);

        $this->assertNotSame($service->cacheKey($first), $service->cacheKey($second));
        $this->assertStringNotContainsString('secret-api-key', $service->cacheKey($first));
        $this->assertStringNotContainsString('customer', $service->cacheKey($first));
    }

    public function test_force_refresh_bypasses_and_updates_cached_success(): void
    {
        [$user, $account] = $this->userAndAccount();
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->twice()->andReturn(
                $this->paymentResult('1,200.00'),
                $this->paymentResult('9,999.99'),
            );
        });

        $service = app(DashboardPaymentOverviewService::class);
        $service->get($user, $account);
        $refreshed = $service->get($user, $account, forceRefresh: true);
        $hit = $service->get($user, $account);

        $this->assertSame('9,999.99', $refreshed['summary']['total_amount_display']);
        $this->assertSame('refreshed', $refreshed['cache']['status']);
        $this->assertSame('9,999.99', $hit['summary']['total_amount_display']);
        $this->assertSame('hit', $hit['cache']['status']);
    }

    public function test_exceptions_are_not_cached_but_legitimate_empty_success_is_cached(): void
    {
        [$user, $account] = $this->userAndAccount();
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->once()->andThrow(new SisahygoServerException('Server unavailable.', 500));
            $mock->shouldReceive('list')->once()->andReturn($this->paymentResult('0.00', []));
        });

        $service = app(DashboardPaymentOverviewService::class);
        $failed = $service->get($user, $account);
        $empty = $service->get($user, $account);
        $hit = $service->get($user, $account);

        $this->assertFalse($failed['available']);
        $this->assertTrue($empty['available']);
        $this->assertSame([], $empty['recent']);
        $this->assertSame('hit', $hit['cache']['status']);
    }

    public function test_force_refresh_failure_can_show_marked_cached_data_without_overwriting_it(): void
    {
        [$user, $account] = $this->userAndAccount();
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->once()->andReturn($this->paymentResult('1,200.00'));
            $mock->shouldReceive('list')->once()->andThrow(new SisahygoServerException('Server unavailable.', 500));
        });

        $service = app(DashboardPaymentOverviewService::class);
        $service->get($user, $account);
        $stale = $service->get($user, $account, forceRefresh: true);

        $this->assertTrue($stale['available']);
        $this->assertTrue($stale['cache']['is_stale']);
        $this->assertSame('stale_on_error', $stale['cache']['status']);
        $this->assertSame('1,200.00', $stale['summary']['total_amount_display']);
        $this->assertNotNull(Cache::get($service->cacheKey($account)));
    }

    public function test_cache_disabled_or_invalid_ttl_fetches_directly(): void
    {
        [$user, $account] = $this->userAndAccount();
        config()->set('sisahygo.dashboard.payment_cache_ttl', -15);
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->twice()->andReturn($this->paymentResult('1.00'));
        });

        $service = app(DashboardPaymentOverviewService::class);
        $this->assertSame(0, $service->ttlSeconds());
        $service->get($user, $account);
        $second = $service->get($user, $account);

        $this->assertSame('disabled', $second['cache']['status']);
    }

    public function test_one_account_invalidation_does_not_affect_another_account(): void
    {
        [$user, $first] = $this->userAndAccount();
        $second = ClientAccount::factory()->create(['name' => 'Second Account']);
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->twice()->andReturn($this->paymentResult('1.00'), $this->paymentResult('2.00'));
        });

        $service = app(DashboardPaymentOverviewService::class);
        $service->get($user, $first);
        $service->get($user, $second);
        $service->invalidate($first);

        $this->assertNull(Cache::get($service->cacheKey($first)));
        $this->assertNotNull(Cache::get($service->cacheKey($second)));
    }

    public function test_cache_store_failure_falls_back_to_direct_core_fetch(): void
    {
        [$user, $account] = $this->userAndAccount();
        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('get')->once()->andThrow(new RuntimeException('cache down'));
        $cache->shouldReceive('put')->once()->andThrow(new RuntimeException('cache down'));
        $this->app->instance(CacheRepository::class, $cache);
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->once()->andReturn($this->paymentResult('7.00'));
        });

        $result = app(DashboardPaymentOverviewService::class)->get($user, $account);

        $this->assertTrue($result['available']);
        $this->assertSame('7.00', $result['summary']['total_amount_display']);
    }

    public function test_safe_timing_log_contains_cache_status_without_api_key_or_body(): void
    {
        [$user, $account] = $this->userAndAccount();
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug')->atLeast()->once()->with(Mockery::type('string'), Mockery::on(function (array $context): bool {
            $encoded = json_encode($context);

            return isset($context['duration_ms'], $context['cache_status'])
                && ! str_contains($encoded, 'secret-api-key')
                && ! str_contains($encoded, 'response_payload')
                && ! str_contains($encoded, 'request_payload');
        }));
        $this->mock(PaymentQueryService::class, function ($mock): void {
            $mock->shouldReceive('list')->once()->andReturn($this->paymentResult('1.00'));
        });

        app(DashboardPaymentOverviewService::class)->get($user, $account);
    }

    /** @return array{0: User, 1: ClientAccount} */
    private function userAndAccount(): array
    {
        return [User::factory()->create(), ClientAccount::factory()->create(['name' => 'Selected Account'])];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function paymentResult(string $total, array $items = [['payment_identifier' => 'AR-P-1001', 'order_header_no' => 'OH1001']]): array
    {
        return [
            'items' => $items,
            'summary' => [
                'record_count' => count($items),
                'total_amount' => str_replace(',', '', $total),
                'total_amount_display' => $total,
                'paid_record_count' => 1,
                'outstanding_record_count' => 0,
            ],
            'meta' => ['current_page' => 1, 'per_page' => 5, 'total' => count($items), 'last_page' => 1],
            'filters' => ['page' => 1, 'per_page' => 5],
        ];
    }
}
