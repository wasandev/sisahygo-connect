<?php

use App\Http\Controllers\ClientAccountSelectionController;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

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
        Route::view('/dashboard', 'dashboard')
            ->name('dashboard');

        Route::view('/order-checking', 'pages.placeholder', [
            'title' => __('navigation.order_checking'),
            'description' => __('page.placeholder.order_checking'),
        ])->name('order-checking');

        Route::view('/shipments', 'pages.placeholder', [
            'title' => __('navigation.shipments'),
            'description' => __('page.placeholder.shipments'),
        ])->name('shipments');

        Route::view('/tracking', 'pages.placeholder', [
            'title' => __('navigation.tracking'),
            'description' => __('page.placeholder.tracking'),
        ])->name('tracking');

        Route::view('/history', 'pages.placeholder', [
            'title' => __('navigation.history'),
            'description' => __('page.placeholder.history'),
        ])->name('history');

        Route::view('/payments', 'pages.placeholder', [
            'title' => __('navigation.payments'),
            'description' => __('page.placeholder.payments'),
        ])->name('payments');

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
