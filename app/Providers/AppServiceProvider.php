<?php

namespace App\Providers;

use App\Domain\Audit\Contracts\RecordsClientAccountActivity;
use App\Domain\Audit\Services\ClientAccountActivityLogger;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\ClientAccount\Policies\ClientAccountPolicy;
use App\Domain\ClientAccount\Policies\ClientAccountUserPolicy;
use App\Domain\Payment\Policies\PaymentPolicy;
use App\Domain\Shipment\Policies\ShipmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RecordsClientAccountActivity::class, ClientAccountActivityLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ClientAccount::class, ClientAccountPolicy::class);
        Gate::policy(ClientAccountUser::class, ClientAccountUserPolicy::class);
        Gate::define('shipment.viewAny', [ShipmentPolicy::class, 'viewAny']);
        Gate::define('shipment.viewHistory', [ShipmentPolicy::class, 'viewHistory']);
        Gate::define('shipment.export', [ShipmentPolicy::class, 'export']);
        Gate::define('payment.viewAny', [PaymentPolicy::class, 'viewAny']);
        Gate::define('payment.download', [PaymentPolicy::class, 'download']);
    }
}