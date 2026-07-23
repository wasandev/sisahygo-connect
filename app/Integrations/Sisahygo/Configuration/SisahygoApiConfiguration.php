<?php

namespace App\Integrations\Sisahygo\Configuration;

use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use InvalidArgumentException;
use RuntimeException;

final readonly class SisahygoApiConfiguration
{
    public function __construct(
        public SisahygoApiEnvironment $environment,
        public string $baseUrl,
        public int $connectTimeout,
        public int $timeout,
        public int $retryTimes,
        public int $retrySleepMs,
        public string $userAgent,
        public bool $liveSmokeTests,
    ) {}

    public static function fromConfig(): self
    {
        $environment = SisahygoApiEnvironment::tryFrom((string) config('sisahygo.api.environment'));

        if (! $environment) {
            throw new InvalidArgumentException('Unsupported Sisahygo API environment.');
        }

        $baseUrl = self::baseUrlForEnvironment($environment);

        return new self(
            environment: $environment,
            baseUrl: $baseUrl,
            connectTimeout: max(1, (int) config('sisahygo.api.connect_timeout')),
            timeout: max(1, (int) config('sisahygo.api.timeout')),
            retryTimes: max(0, (int) config('sisahygo.api.retry_times')),
            retrySleepMs: max(0, (int) config('sisahygo.api.retry_sleep_ms')),
            userAgent: (string) config('sisahygo.api.user_agent'),
            liveSmokeTests: (bool) config('sisahygo.api.live_smoke_tests'),
        );
    }

    public static function baseUrlForEnvironment(SisahygoApiEnvironment $environment): string
    {
        $baseUrl = trim((string) (config('sisahygo.api.base_url') ?: config("sisahygo.api.environments.{$environment->value}.base_url")));

        if ($baseUrl === '') {
            throw new RuntimeException("Sisahygo API base URL is not configured for {$environment->value}.");
        }

        self::assertTrustedBaseUrl($baseUrl);
        self::assertApplicationEnvironmentMatches($environment, $baseUrl);

        if ($environment === SisahygoApiEnvironment::Production && str_contains(strtolower((string) parse_url($baseUrl, PHP_URL_HOST)), 'sandbox')) {
            throw new RuntimeException('Sisahygo production environment cannot use a sandbox API host.');
        }

        return rtrim($baseUrl, '/');
    }

    public static function assertTrustedBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);

        if (($parts['scheme'] ?? null) !== 'https' || blank($parts['host'] ?? null)) {
            throw new InvalidArgumentException('Sisahygo API base URL must be an HTTPS URL from trusted configuration.');
        }
    }

    public static function host(string $baseUrl): string
    {
        return strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
    }

    private static function assertApplicationEnvironmentMatches(SisahygoApiEnvironment $environment, string $baseUrl): void
    {
        $appEnvironment = (string) config('app.env');
        $host = self::host($baseUrl);
        $sandboxHost = self::configuredHost('sandbox');
        $productionHost = self::configuredHost('production');

        if ($appEnvironment === 'staging') {
            if ($environment !== SisahygoApiEnvironment::Sandbox || ($sandboxHost !== '' && $host !== $sandboxHost)) {
                throw new RuntimeException('Staging must use the Sisahygo sandbox API endpoint.');
            }

            return;
        }

        if ($appEnvironment === 'production') {
            if ($environment !== SisahygoApiEnvironment::Production) {
                throw new RuntimeException('Production must use the Sisahygo production API environment.');
            }

            if ($productionHost !== '' && $host !== $productionHost) {
                throw new RuntimeException('Production must use the Sisahygo production API endpoint.');
            }
        }
    }

    private static function configuredHost(string $environment): string
    {
        $baseUrl = trim((string) config("sisahygo.api.environments.{$environment}.base_url"));

        return $baseUrl === '' ? '' : self::host($baseUrl);
    }
}
