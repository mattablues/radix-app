<?php

declare(strict_types=1);

$env = getenv('APP_ENV') ?: 'production';

return [
    // Slå av/på CORS helt. Default: på i dev/test, av i ren prod.
    'enabled' => $env !== 'production',

    // Vilka path-prefix som ska få CORS (enkelt första version: hela API:t)
    'paths' => [
        '/api/v1/', // allt under /api/v1
    ],

    // Tillåtna origins
    'allow_origins' => [
        getenv('CORS_ALLOW_ORIGIN') ?: '*',
    ],

    // Tillåtna metoder
    'allow_methods' => [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ],

    // Tillåtna headers
    'allow_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-Request-Id',
    ],

    // Headers som exponeras till JS
    'expose_headers' => [
        'X-Request-Id',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    // Hur länge preflight får cachas (sekunder)
    'max_age' => 600,

    // Om cookies/credentials får skickas
    'allow_credentials' => getenv('CORS_ALLOW_CREDENTIALS') === '1',
];
