<?php

namespace App\Providers;

use App\Console\Commands\SisahygoCredentialSetCommand;
use App\Console\Commands\SisahygoDiagnosticsCommand;
use App\Console\Commands\SisahygoIntegrationStatusCommand;
use App\Console\Commands\SisahygoSmokeTestCommand;
use App\Domain\Sisahygo\Services\SisahygoApiCredentialService;
use App\Integrations\Sisahygo\Configuration\SisahygoApiConfiguration;
use Illuminate\Support\ServiceProvider;

class SisahygoIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SisahygoApiConfiguration::class, fn () => SisahygoApiConfiguration::fromConfig());
        $this->app->singleton(SisahygoApiCredentialService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SisahygoCredentialSetCommand::class,
                SisahygoDiagnosticsCommand::class,
                SisahygoIntegrationStatusCommand::class,
                SisahygoSmokeTestCommand::class,
            ]);
        }
    }
}
