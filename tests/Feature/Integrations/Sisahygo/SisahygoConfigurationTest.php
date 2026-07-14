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
}