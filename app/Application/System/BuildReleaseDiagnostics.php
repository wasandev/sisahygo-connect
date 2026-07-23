<?php

namespace App\Application\System;

use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class BuildReleaseDiagnostics
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'application' => config('app.name', 'Sisahygo Connect'),
            'app_environment' => app()->environment(),
            'app_debug' => (bool) config('app.debug'),
            'app_url' => config('app.url'),
            'release_identifier' => $this->releaseIdentifier(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'api_environment' => (string) config('sisahygo.api.environment'),
            'api_host' => $this->apiHost(),
            'cache_store' => config('cache.default'),
            'cache_status' => $this->cacheStatus(),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
            'database_connection' => config('database.default'),
            'database_status' => $this->databaseStatus(),
        ];
    }

    private function releaseIdentifier(): string
    {
        foreach (['version', 'build', 'commit'] as $key) {
            $value = $this->safeReleaseValue(config("sisahygo.release.{$key}"));

            if ($value !== '') {
                return $value;
            }
        }

        return $this->gitCommit();
    }

    private function safeReleaseValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return substr((string) preg_replace('/[^A-Za-z0-9._-]/', '', $value), 0, 40);
    }

    private function gitCommit(): string
    {
        $headPath = base_path('.git/HEAD');

        if (! is_file($headPath)) {
            return 'unavailable';
        }

        $head = trim((string) file_get_contents($headPath));

        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/'.substr($head, 5));

            return is_file($refPath) ? substr(trim((string) file_get_contents($refPath)), 0, 12) : 'unavailable';
        }

        return $head !== '' ? substr($head, 0, 12) : 'unavailable';
    }

    private function apiHost(): string
    {
        try {
            $baseUrl = SisahygoApiConfiguration::fromConfig()->baseUrl;
        } catch (Throwable) {
            return 'unconfigured';
        }

        return parse_url($baseUrl, PHP_URL_HOST) ?: 'unavailable';
    }

    private function cacheStatus(): string
    {
        try {
            Cache::put('sisahygo-diagnostics-check', 'ok', 5);

            return Cache::get('sisahygo-diagnostics-check') === 'ok' ? 'ok' : 'unavailable';
        } catch (Throwable) {
            return 'unavailable';
        } finally {
            try {
                Cache::forget('sisahygo-diagnostics-check');
            } catch (Throwable) {
                // Best-effort cleanup only.
            }
        }
    }

    private function databaseStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (Throwable) {
            return 'unavailable';
        }
    }
}
