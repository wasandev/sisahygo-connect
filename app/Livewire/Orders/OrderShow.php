<?php

namespace App\Livewire\Orders;

use App\Livewire\Shipments\ShipmentShow;
use Illuminate\Contracts\View\View;

class OrderShow extends ShipmentShow
{
    public function render(): View
    {
        return view('livewire.orders.show')->layout('layouts.app', [
            'title' => __('orders.detail.title'),
        ]);
    }
}
