<?php

return [
    'api' => [
        'environment' => env('SISAHYGO_API_ENVIRONMENT', 'sandbox'),
        'environments' => [
            'sandbox' => [
                'base_url' => env('SISAHYGO_API_SANDBOX_URL', 'https://sandbox-api.sisahygo.online/api/v1/client'),
            ],
            'production' => [
                'base_url' => env('SISAHYGO_API_PRODUCTION_URL', 'https://api.sisahygo.online/api/v1/client'),
            ],
        ],
        'connect_timeout' => (int) env('SISAHYGO_API_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('SISAHYGO_API_TIMEOUT', 15),
        'retry_times' => (int) env('SISAHYGO_API_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('SISAHYGO_API_RETRY_SLEEP_MS', 250),
        'user_agent' => env('SISAHYGO_API_USER_AGENT', 'Sisahygo Connect'),
        'live_smoke_tests' => (bool) env('SISAHYGO_API_LIVE_SMOKE_TESTS', false),
    ],
];