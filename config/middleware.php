<?php

declare(strict_types=1);

return [
    // Sätt säkra headers och nonce först
    'security.headers' => \Radix\Middleware\Middlewares\SecurityHeaders::class,
    'limit.2mb' => \Radix\Middleware\Middlewares\LimitRequestSize::class,
    'csrf' => \App\Middlewares\Csrf::class, // byt till klassnamn
    'auth' => \App\Middlewares\Auth::class,
    'guest' => \App\Middlewares\Guest::class,
    'admin' => \App\Middlewares\Admin::class,
    'private' => \App\Middlewares\PrivateApp::class,
    'location' => \App\Middlewares\Location::class,
    'request.id' => \Radix\Middleware\Middlewares\RequestId::class,
    'ip.allowlist' => \App\Middlewares\IpAllowlist::class,
    'role.exact.admin' => \App\Middlewares\RequireAdmin::class,
    'role.min.moderator' => \App\Middlewares\RequireModeratorOrHigher::class,
    'role.min.editor' => \App\Middlewares\RequireEditorOrHigher::class,
    'role.min.support' => \App\Middlewares\RequireSupportOrHigher::class,
    'share.user' => \App\Middlewares\ShareCurrentUser::class,
    // API-observability
    'api.logger' => \App\Middlewares\RequestLogger::class,
    'api.throttle' => \Radix\Middleware\Middlewares\RateLimiter::class,
    // Policies
    'api.throttle.light' => \Radix\Middleware\Middlewares\RateLimiterLight::class,
    'api.throttle.hard'  => \Radix\Middleware\Middlewares\RateLimiterHard::class,
];
