<?php

declare(strict_types=1);

$env = strtolower((string) (getenv('APP_ENV') ?: 'production'));
$isDev = in_array($env, ['dev', 'development', 'local', 'test'], true);
$isProd = in_array($env, ['prod', 'production'], true);

$connectSrc = ["'self'"];
if ($isDev) {
    $connectSrc[] = 'http://localhost:5173';
}

return [
    'csp' => [
        'web' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'script-src' => [
                "'self'",
                function (): string {
                    return "'nonce-" . csp_nonce() . "'";
                },
            ],
            'img-src' => ["'self'", 'data:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => $connectSrc,
            'frame-ancestors' => ["'none'"],
            'form-action' => ["'self'"],
        ],
        'api' => [
            'default-src' => ["'none'"],
            'base-uri' => ["'none'"],
            'object-src' => ["'none'"],
            'frame-ancestors' => ["'none'"],
            'form-action' => ["'none'"],
        ],
        'enable_hsts' => $isProd,
    ],
];
