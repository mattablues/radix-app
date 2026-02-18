<?php

declare(strict_types=1);

return [
    // Sätt säkra headers och nonce först
    'security.headers' => \Radix\Middleware\Middlewares\SecurityHeaders::class,
    'limit.2mb' => \Radix\Middleware\Middlewares\LimitRequestSize::class,
    'csrf' => \App\Middlewares\Csrf::class, // byt till klassnamn
    'private' => \App\Middlewares\PrivateApp::class,
    'location' => \App\Middlewares\Location::class,
    'request.id' => \Radix\Middleware\Middlewares\RequestId::class,
    'api.logger' => \App\Middlewares\RequestLogger::class,
    'api.throttle' => \Radix\Middleware\Middlewares\RateLimiter::class,
    // Policies
    'api.throttle.light' => \Radix\Middleware\Middlewares\RateLimiterLight::class,
    'api.throttle.hard'  => \Radix\Middleware\Middlewares\RateLimiterHard::class,
];
