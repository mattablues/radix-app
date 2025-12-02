<?php

declare(strict_types=1);

return [
    'web' => [
        'default-src' => ["'self'"],
        'style-src'   => ["'self'", "'unsafe-inline'"],
        'script-src'  => [
            "'self'",
            function (): string {
                return "'nonce-" . csp_nonce() . "'";
            },
        ],
        'img-src'     => ["'self'", 'data:'],
        'font-src'    => ["'self'", 'data:'],
        'connect-src' => ["'self'", 'http://localhost:5173'],
        'frame-ancestors' => ["'none'"],
    ],
    'api' => [
        'default-src' => ["'none'"],
        'frame-ancestors' => ["'none'"],
    ],
    'enable_hsts' => true,
];