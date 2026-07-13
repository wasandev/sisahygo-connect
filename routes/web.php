<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::view('/order-checking', 'pages.placeholder', [
        'title' => 'Order Checking',
        'description' => 'Order checking tools will be connected here in a future release.',
    ])->name('order-checking');

    Route::view('/shipments', 'pages.placeholder', [
        'title' => 'Shipments',
        'description' => 'Shipment management will be available here when the integration is ready.',
    ])->name('shipments');

    Route::view('/tracking', 'pages.placeholder', [
        'title' => 'Tracking',
        'description' => 'Tracking tools will be available here when the workflow is connected.',
    ])->name('tracking');

    Route::view('/history', 'pages.placeholder', [
        'title' => 'History',
        'description' => 'Historical activity and completed shipment records will appear here.',
    ])->name('history');

    Route::view('/payments', 'pages.placeholder', [
        'title' => 'Payments',
        'description' => 'Payment summaries and billing workflows will be prepared here.',
    ])->name('payments');

    Route::view('/reports', 'pages.placeholder', [
        'title' => 'Reports',
        'description' => 'Operational reports will be available here in a future release.',
    ])->name('reports');

    Route::view('/settings', 'settings.client-account')
        ->name('settings');

    Route::view('/settings/client-account', 'settings.client-account')
        ->name('settings.client-account');

    Route::view('/profile', 'profile')
        ->name('profile');

    Route::post('/logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');

});

require __DIR__.'/auth.php';