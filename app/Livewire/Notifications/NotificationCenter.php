<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationCenter extends Component
{
    public string $filter = 'all';

    /** @return array<int, array<string, string|bool>> */
    public function notifications(): array
    {
        $items = [
            ['id' => 'mock-1', 'type' => 'shipment', 'title' => __('notifications.mock.shipment.title'), 'message' => __('notifications.mock.shipment.message'), 'time' => '2026-07-17 10:30', 'read' => false],
            ['id' => 'mock-2', 'type' => 'payment', 'title' => __('notifications.mock.payment.title'), 'message' => __('notifications.mock.payment.message'), 'time' => '2026-07-16 15:45', 'read' => false],
            ['id' => 'mock-3', 'type' => 'system', 'title' => __('notifications.mock.system.title'), 'message' => __('notifications.mock.system.message'), 'time' => '2026-07-15 09:00', 'read' => true],
        ];

        if ($this->filter === 'unread') {
            return array_values(array_filter($items, fn (array $item): bool => ! $item['read']));
        }

        return $items;
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-center', [
            'notifications' => $this->notifications(),
        ])->layout('layouts.app', [
            'title' => __('notifications.title'),
        ]);
    }
}
