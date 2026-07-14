<?php

namespace App\Integrations\Sisahygo\Configuration;

use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use InvalidArgumentException;

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

        $baseUrl = (string) config("sisahygo.api.environments.{$environment->value}.base_url");
        self::assertTrustedBaseUrl($baseUrl);

        return new self(
            environment: $environment,
            baseUrl: rtrim($baseUrl, '/'),
            connectTimeout: max(1, (int) config('sisahygo.api.connect_timeout')),
            timeout: max(1, (int) config('sisahygo.api.timeout')),
            retryTimes: max(0, (int) config('sisahygo.api.retry_times')),
            retrySleepMs: max(0, (int) config('sisahygo.api.retry_sleep_ms')),
            userAgent: (string) config('sisahygo.api.user_agent'),
            liveSmokeTests: (bool) config('sisahygo.api.live_smoke_tests'),
        );
    }

    public static function assertTrustedBaseUrl(string $baseUrl): void
    {
        $parts = parse_url($baseUrl);

        if (($parts['scheme'] ?? null) !== 'https' || blank($parts['host'] ?? null)) {
            throw new InvalidArgumentException('Sisahygo API base URL must be an HTTPS URL from trusted configuration.');
        }
    }
}