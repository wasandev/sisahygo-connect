<?php

use App\Http\Controllers\ClientAccountSelectionController;
use App\Livewire\Actions\Logout;
use App\Livewire\Dashboard\CustomerDashboard;
use App\Livewire\History\OrderHistory;
use App\Livewire\OrderChecking;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentShow;
use App\Livewire\Shipments\ShipmentIndex;
use App\Livewire\Shipments\ShipmentShow;
use App\Livewire\Shipments\TrackingLookup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('welcome');

Route::middleware(['auth'])->group(function () {
    // Account selection is authenticated but intentionally outside the tenant middleware
    // because users with multiple accounts need a safe place to choose the active tenant.
    Route::get('/client-accounts/select', [ClientAccountSelectionController::class, 'index'])
        ->name('client-accounts.select');
    Route::post('/client-accounts/select', [ClientAccountSelectionController::class, 'store'])
        ->name('client-accounts.select.store');
    Route::post('/client-accounts/change', [ClientAccountSelectionController::class, 'change'])
        ->name('client-accounts.change');

    // Profile and logout remain user-level routes and do not require a selected tenant.
    Route::view('/profile', 'profile')
        ->name('profile');

    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');

    Route::middleware(['client.account'])->group(function () {
        Route::prefix('ux')->name('ux.')->group(function () {
            Route::view('/dashboard', 'ux.dashboard')->name('dashboard');
            Route::get('/order-checking', OrderChecking::class)->name('order-checking');
            Route::view('/tracking', 'ux.tracking')->name('tracking');
            Route::view('/shipment-detail', 'ux.shipment-detail')->name('shipment-detail');
            Route::view('/payments', 'ux.payments')->name('payments');
            Route::view('/reports', 'ux.reports')->name('reports');
            Route::view('/settings', 'ux.settings')->name('settings');
            Route::view('/profile', 'ux.profile')->name('profile');
            Route::view('/notifications', 'ux.notifications')->name('notifications');
        });
        Route::get('/dashboard', CustomerDashboard::class)
            ->name('dashboard');

        Route::get('/order-checking', OrderChecking::class)
            ->name('order-checking');

        Route::get('/shipments', ShipmentIndex::class)
            ->name('shipments');
        Route::get('/shipments/{trackingIdentifier}', ShipmentShow::class)
            ->name('shipments.show');

        Route::get('/tracking', TrackingLookup::class)
            ->name('tracking');

        Route::get('/history', OrderHistory::class)
            ->name('history');

        Route::get('/payments', PaymentIndex::class)
            ->name('payments');
        Route::get('/payments/{paymentIdentifier}', PaymentShow::class)
            ->name('payments.show');

        Route::view('/reports', 'pages.placeholder', [
            'title' => __('navigation.reports'),
            'description' => __('page.placeholder.reports'),
        ])->name('reports');

        Route::view('/settings', 'settings.client-account')
            ->name('settings');

        Route::view('/settings/client-account', 'settings.client-account')
            ->name('settings.client-account');
    });
});

require __DIR__.'/auth.php';
