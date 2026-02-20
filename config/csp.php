<?php

declare(strict_types=1);

$env = strtolower((string) (getenv('APP_ENV') ?: 'production'));
$isDev = in_array($env, ['dev', 'development', 'local', 'test'], true);
$env = strtolower((string) (getenv('APP_ENV') ?: 'production'));
$isProd = in_array($env, ['prod', 'production'], true);

$connectSrc = ["'self'"];
if ($isDev) {
    $connectSrc[] = 'http://localhost:5173';
}

return [
    'csp' => [
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
            'connect-src' => $connectSrc,
            'frame-ancestors' => ["'none'"],
        ],
        'api' => [
            'default-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
        ],
        'enable_hsts' => $isProd,
    ],
];
