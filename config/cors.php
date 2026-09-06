<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [
        '#^https?://.*\.ngrok-free\.app$#',
        '#^https?://.*\.ngrok\.io$#',
        '#^http://localhost:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
