<?php

return [
    'name' => env('CONNECT_NAME', 'Sisahygo Connect'),
    'api' => [
        // Deprecated compatibility keys. New integration code must use config/sisahygo.php.
        'base_url' => env('SISAHYGO_API_BASE_URL') ?: env('SISAHYGO_API_SANDBOX_URL'),
        'timeout' => (int) env('SISAHYGO_API_TIMEOUT', 15),
    ],
    'brand' => [
        'primary' => '#0875D1',
        'accent' => '#F47A16',
        'ink' => '#0B2A4A',
    ],
];