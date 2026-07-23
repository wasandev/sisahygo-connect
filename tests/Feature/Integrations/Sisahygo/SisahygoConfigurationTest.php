<?php

namespace Tests\Feature\Integrations\Sisahygo;

use App\Domain\Sisahygo\Enums\SisahygoApiEnvironment;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use InvalidArgumentException;
use Tests\TestCase;

class SisahygoConfigurationTest extends TestCase
{
    public function test_sandbox_url_resolution(): void
    {
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.environments.sandbox.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');

        $configuration = SisahygoApiConfiguration::fromConfig();

        $this->assertSame(SisahygoApiEnvironment::Sandbox, $configuration->environment);
        $this->assertSame('https://sandbox-api.sisahygo.online/api/v1/client', $configuration->baseUrl);
    }

    public function test_production_url_resolution(): void
    {
        config()->set('sisahygo.api.environment', 'production');
        config()->set('sisahygo.api.environments.production.base_url', 'https://api.sisahygo.online/api/v1/client');

        $configuration = SisahygoApiConfiguration::fromConfig();

        $this->assertSame(SisahygoApiEnvironment::Production, $configuration->environment);
        $this->assertSame('https://api.sisahygo.online/api/v1/client', $configuration->baseUrl);
    }

    public function test_staging_accepts_only_sandbox_endpoint(): void
    {
        config()->set('app.env', 'staging');
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.environments.sandbox.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');

        $configuration = SisahygoApiConfiguration::fromConfig();

        $this->assertSame(SisahygoApiEnvironment::Sandbox, $configuration->environment);
        $this->assertSame('https://sandbox-api.sisahygo.online/api/v1/client', $configuration->baseUrl);
    }

    public function test_staging_rejects_production_endpoint(): void
    {
        config()->set('app.env', 'staging');
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.base_url', 'https://api.sisahygo.online/api/v1/client');
        config()->set('sisahygo.api.environments.sandbox.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Staging must use the Sisahygo sandbox API endpoint.');

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_staging_rejects_production_api_environment(): void
    {
        config()->set('app.env', 'staging');
        config()->set('sisahygo.api.environment', 'production');
        config()->set('sisahygo.api.environments.production.base_url', 'https://api.sisahygo.online/api/v1/client');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Staging must use the Sisahygo sandbox API endpoint.');

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_production_rejects_sandbox_api_environment(): void
    {
        config()->set('app.env', 'production');
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.environments.sandbox.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Production must use the Sisahygo production API environment.');

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_unsupported_environment_is_rejected(): void
    {
        config()->set('sisahygo.api.environment', 'preview');

        $this->expectException(InvalidArgumentException::class);

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_non_https_base_url_is_rejected(): void
    {
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.environments.sandbox.base_url', 'http://example.test/api');

        $this->expectException(InvalidArgumentException::class);

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_missing_base_url_fails_clearly(): void
    {
        config()->set('sisahygo.api.environment', 'sandbox');
        config()->set('sisahygo.api.base_url', null);
        config()->set('sisahygo.api.environments.sandbox.base_url', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sisahygo API base URL is not configured');

        SisahygoApiConfiguration::fromConfig();
    }

    public function test_production_cannot_use_sandbox_host(): void
    {
        config()->set('sisahygo.api.environment', 'production');
        config()->set('sisahygo.api.environments.production.base_url', 'https://sandbox-api.sisahygo.online/api/v1/client');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('production environment cannot use a sandbox API host');

        SisahygoApiConfiguration::fromConfig();
    }
}
