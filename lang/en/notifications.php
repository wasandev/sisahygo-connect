<?php

return [
    'eyebrow' => 'Notification Center',
    'title' => 'Notifications',
    'description' => 'Workspace notification center phase 1 with mock data only.',
    'center_title' => 'Notification list',
    'unread' => 'Unread',
    'phase_one' => [
        'title' => 'Phase 1: Mock Data',
        'message' => 'No polling, push notifications, or persisted read state are enabled yet.',
    ],
    'filters' => [
        'label' => 'Notification filters',
        'all' => 'All',
        'unread' => 'Unread',
    ],
    'empty' => [
        'title' => 'No notifications',
        'description' => 'Real system notifications will appear here once connected.',
    ],
    'mock' => [
        'shipment' => ['title' => 'Shipment needs attention', 'message' => 'OH90001 is in a problem state. Please check with the destination branch.'],
        'payment' => ['title' => 'Outstanding payment detected', 'message' => 'A new outstanding balance is visible in Payment Center for this Client Account.'],
        'system' => ['title' => 'Sisahygo data is available', 'message' => 'The latest Core API connection succeeded in the Sandbox environment.'],
    ],
];
