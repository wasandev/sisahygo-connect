<?php

namespace App\Application\Dashboard;

use App\Application\Payment\PaymentQueryService;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Integrations\Sisahygo\Exceptions\SisahygoApiException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardPaymentOverviewService
{
    private const RECENT_LIMIT = 5;

    public function __construct(
        private readonly PaymentQueryService $payments,
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(User $user, ClientAccount $clientAccount, bool $forceRefresh = false): array
    {
        $startedAt = microtime(true);
        $cacheKey = $this->cacheKey($clientAccount);
        $cached = $this->readCache($cacheKey, $clientAccount);

        if (! $forceRefresh && $cached !== null && $this->cacheEnabled()) {
            $this->log('sisahygo.payment.dashboard.cache_hit', $clientAccount, $startedAt, [
                'cache_status' => 'hit',
                'result_count' => count($cached['recent'] ?? []),
                'success' => true,
            ]);

            return $this->withCacheMeta($cached, 'hit');
        }

        $this->log('sisahygo.payment.dashboard.cache_miss', $clientAccount, $startedAt, [
            'cache_status' => $forceRefresh ? 'bypass' : ($this->cacheEnabled() ? 'miss' : 'disabled'),
            'success' => true,
        ]);

        try {
            $overview = $this->fetch($user, $clientAccount);
            $overview = $this->withCacheMeta($overview, $forceRefresh ? 'refreshed' : ($this->cacheEnabled() ? 'miss' : 'disabled'));
            $this->writeCache($cacheKey, $overview, $clientAccount);

            $this->log('sisahygo.payment.dashboard.fetch_completed', $clientAccount, $startedAt, [
                'cache_status' => $overview['cache']['status'],
                'result_count' => count($overview['recent'] ?? []),
                'success' => true,
            ]);

            return $overview;
        } catch (ModelNotFoundException|AuthorizationException|SisahygoApiException $exception) {
            if ($forceRefresh && $cached !== null) {
                $stale = $this->withCacheMeta($cached, 'stale_on_error', true);
                $stale['warning'] = __('dashboard.payments.cache.stale_warning');

                $this->log('sisahygo.payment.dashboard.fetch_failed', $clientAccount, $startedAt, [
                    'cache_status' => 'stale_on_error',
                    'http_status_category' => $this->statusCategory($exception),
                    'result_count' => count($stale['recent'] ?? []),
                    'success' => false,
                    'exception_category' => $exception::class,
                ]);

                return $stale;
            }

            $this->log('sisahygo.payment.dashboard.fetch_failed', $clientAccount, $startedAt, [
                'cache_status' => $forceRefresh ? 'bypass_failed' : 'miss_failed',
                'http_status_category' => $this->statusCategory($exception),
                'success' => false,
                'exception_category' => $exception::class,
            ]);

            return $this->unavailable();
        }
    }

    public function invalidate(ClientAccount $clientAccount): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $this->forget($this->cacheKey($clientAccount), $clientAccount);
    }

    public function cacheKey(ClientAccount $clientAccount): string
    {
        $environment = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) config('sisahygo.api.environment', 'sandbox'));
        $locale = preg_replace('/[^A-Za-z0-9_-]/', '-', app()->getLocale());

        return sprintf('sisahygo-connect:dashboard:payments:%s:locale:%s:account:%d:query:v1:page-1:per-page-%d', $environment, $locale, $clientAccount->getKey(), self::RECENT_LIMIT);
    }

    public function ttlSeconds(): int
    {
        $ttl = (int) config('sisahygo.dashboard.payment_cache_ttl', 60);

        return max(0, min(300, $ttl));
    }

    /** @return array<string, mixed>|null */
    private function readCache(string $cacheKey, ClientAccount $clientAccount): ?array
    {
        if (! $this->cacheEnabled()) {
            return null;
        }

        try {
            $cached = $this->cache->get($cacheKey);

            return is_array($cached) && ($cached['available'] ?? false) === true ? $cached : null;
        } catch (Throwable $exception) {
            $this->log('sisahygo.payment.dashboard.cache_read_failed', $clientAccount, microtime(true), [
                'cache_status' => 'read_failed',
                'success' => false,
                'exception_category' => $exception::class,
            ]);

            return null;
        }
    }

    /** @param array<string, mixed> $overview */
    private function writeCache(string $cacheKey, array $overview, ClientAccount $clientAccount): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $ttl = $this->ttlSeconds();
        if ($ttl <= 0) {
            return;
        }

        try {
            $this->cache->put($cacheKey, $overview, now()->addSeconds($ttl));
        } catch (Throwable $exception) {
            $this->log('sisahygo.payment.dashboard.cache_write_failed', $clientAccount, microtime(true), [
                'cache_status' => 'write_failed',
                'success' => false,
                'exception_category' => $exception::class,
            ]);
        }
    }

    private function forget(string $cacheKey, ClientAccount $clientAccount): void
    {
        try {
            $this->cache->forget($cacheKey);
        } catch (Throwable $exception) {
            $this->log('sisahygo.payment.dashboard.cache_forget_failed', $clientAccount, microtime(true), [
                'cache_status' => 'forget_failed',
                'success' => false,
                'exception_category' => $exception::class,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function fetch(User $user, ClientAccount $clientAccount): array
    {
        $result = $this->payments->list($user, $clientAccount, [
            'page' => 1,
            'per_page' => self::RECENT_LIMIT,
        ]);

        return [
            'available' => true,
            'error' => null,
            'warning' => null,
            'summary' => $result['summary'],
            'recent' => array_slice($result['items'], 0, self::RECENT_LIMIT),
            'meta' => $result['meta'],
            'links' => $this->links(),
        ];
    }

    /** @return array<string, mixed> */
    private function unavailable(): array
    {
        return [
            'available' => false,
            'error' => __('dashboard.payments.errors.unavailable'),
            'warning' => null,
            'summary' => null,
            'recent' => [],
            'meta' => null,
            'links' => $this->links(),
            'cache' => [
                'status' => 'unavailable',
                'cached_at' => null,
                'is_stale' => false,
            ],
        ];
    }

    /** @param array<string, mixed> $overview */
    private function withCacheMeta(array $overview, string $status, bool $stale = false): array
    {
        $cachedAt = $overview['cache']['cached_at'] ?? now(config('app.timezone'))->format('Y-m-d H:i:s');

        $overview['cache'] = [
            'status' => $status,
            'cached_at' => $cachedAt,
            'is_stale' => $stale,
        ];

        return $overview;
    }

    /** @return array<string, string> */
    private function links(): array
    {
        return [
            'all' => route('payments'),
            'outstanding' => route('payments', ['payment_status' => 'outstanding']),
            'paid' => route('payments', ['payment_status' => 'paid']),
        ];
    }

    private function cacheEnabled(): bool
    {
        return (bool) config('sisahygo.dashboard.payment_cache_enabled', true) && $this->ttlSeconds() > 0;
    }

    private function statusCategory(Throwable $exception): ?string
    {
        if (! $exception instanceof SisahygoApiException || $exception->status === null) {
            return null;
        }

        return ((int) floor($exception->status / 100)).'xx';
    }

    /** @param array<string, mixed> $context */
    private function log(string $event, ClientAccount $clientAccount, float $startedAt, array $context): void
    {
        Log::channel(config('logging.default'))->debug($event, array_merge([
            'operation' => 'dashboard_payment_overview',
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'client_account_id' => $clientAccount->getKey(),
            'environment' => config('sisahygo.api.environment'),
            'query_shape' => 'page=1&per_page=5',
            'locale' => app()->getLocale(),
        ], $context));
    }
}
