<?php

declare(strict_types=1);

return [
    'auth' => \App\Middlewares\Auth::class,
    'guest' => \App\Middlewares\Guest::class,
    'admin' => \App\Middlewares\Admin::class,
    'private' => \App\Middlewares\PrivateApp::class,
    'location' => \App\Middlewares\Location::class,
    'request.id' => \App\Middlewares\RequestId::class,
    'ip.allowlist' => \App\Middlewares\IpAllowlist::class,
    'role.exact.admin' => \App\Middlewares\RequireAdmin::class,
    'role.min.moderator' => \App\Middlewares\RequireModeratorOrHigher::class,
    'role.min.editor' => \App\Middlewares\RequireEditorOrHigher::class,
    'role.min.support' => \App\Middlewares\RequireSupportOrHigher::class,
    // API-observability
    'api.logger' => \App\Middlewares\RequestLogger::class,
    'api.throttle' => \App\Middlewares\RateLimiter::class,
    // Policies
    'api.throttle.light' => \App\Middlewares\RateLimiterLight::class,
    'api.throttle.hard'  => \App\Middlewares\RateLimiterHard::class,
];
