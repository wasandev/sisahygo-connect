<?php

use App\Http\Controllers\ClientAccountSelectionController;
use App\Http\Controllers\Onboarding\AccessRequestController;
use App\Http\Controllers\Onboarding\FirstLoginWelcomeController;
use App\Http\Controllers\Onboarding\InvitationController;
use App\Livewire\Actions\Logout;
use App\Livewire\Dashboard\CustomerDashboard;
use App\Livewire\History\OrderHistory;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\OrderChecking;
use App\Livewire\OrderCheckingBulk;
use App\Livewire\Orders\OrderShow;
use App\Livewire\Payments\PaymentIndex;
use App\Livewire\Payments\PaymentShow;
use App\Livewire\Reports\ReportCenter;
use App\Livewire\Reports\ReportPage;
use App\Http\Controllers\Reports\ReportExportController;
use App\Livewire\Shipments\ShipmentIndex;
use App\Livewire\Shipments\ShipmentShow;
use App\Livewire\Shipments\TrackingLookup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->to(auth()->user()->onboarding_welcomed_at ? route('dashboard') : '/welcome')
        : view('welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/request-access', [AccessRequestController::class, 'create'])->name('request-access');
    Route::post('/request-access', [AccessRequestController::class, 'store'])->name('request-access.store');
    Route::get('/request-access/success', [AccessRequestController::class, 'success'])->name('request-access.success');
    Route::get('/invitation/{token}', [InvitationController::class, 'show'])->middleware('throttle:20,1')->name('invitation.show');
    Route::post('/invitation/{token}', [InvitationController::class, 'activate'])->middleware('throttle:6,1')->name('invitation.activate');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/welcome', [FirstLoginWelcomeController::class, 'show'])->name('onboarding.welcome');
    Route::post('/welcome/start', [FirstLoginWelcomeController::class, 'start'])->name('onboarding.start');

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
        Route::get('/order-checking/bulk', OrderCheckingBulk::class)
            ->name('order-checking.bulk');

        Route::get('/shipments', ShipmentIndex::class)
            ->name('shipments');
        Route::get('/shipments/{trackingIdentifier}', ShipmentShow::class)
            ->name('shipments.show');

        Route::get('/tracking', TrackingLookup::class)
            ->name('tracking');

        Route::get('/history', OrderHistory::class)
            ->name('history');
        Route::get('/orders/{trackingIdentifier}', OrderShow::class)
            ->name('orders.show');

        Route::get('/notifications', NotificationCenter::class)
            ->name('notifications');

        Route::get('/payments', PaymentIndex::class)
            ->name('payments');
        Route::get('/payments/{paymentIdentifier}', PaymentShow::class)
            ->name('payments.show');

        Route::get('/reports', ReportCenter::class)->name('reports');
        Route::get('/reports/shipments', ReportPage::class)->defaults('report', 'shipments')->name('reports.shipments');
        Route::get('/reports/order-checkings', ReportPage::class)->defaults('report', 'order-checkings')->name('reports.order-checkings');
        Route::get('/reports/payments', ReportPage::class)->defaults('report', 'payments')->name('reports.payments');
        Route::get('/reports/{report}/export', ReportExportController::class)->name('reports.export');

        Route::view('/settings', 'settings.client-account')
            ->name('settings');

        Route::view('/settings/client-account', 'settings.client-account')
            ->name('settings.client-account');
    });
});

require __DIR__.'/auth.php';
