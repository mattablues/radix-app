<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

// Toppgrupp: request-id + logging + security headers (CSP) på hela API:t

$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
        // 'limit.2mb',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index'])
        ->name('api.health.index')
        ->middleware(['ip.allowlist']);

    $router->group(['middleware' => ['api.throttle']], function (\Radix\Routing\Router $router) {
        $router->get('/users', [\App\Controllers\Api\UserController::class, 'index'])
            ->name('api.users.index');

        // Sök-endpoints

        // Alla inloggade (session/token) får söka users, men throttla "snällt"
        $router->post('/search/profiles', [\App\Controllers\Api\SearchController::class, 'profiles'])
            ->name('api.search.profiles');

        $router->post('/search/users', [\App\Controllers\Api\SearchController::class, 'users'])
            ->name('api.search.users')
            ->middleware(['role.min.moderator']);

        // Endast moderator+ får söka deleted users
        $router->post('/search/deleted-users', [\App\Controllers\Api\SearchController::class, 'deletedUsers'])
            ->name('api.search.deleted-users')
            ->middleware(['role.min.moderator']);

        // Endast moderator+ får söka system events
        $router->post('/search/system-events', [\App\Controllers\Api\SearchController::class, 'systemEvents'])
            ->name('api.search.system-events')
            ->middleware(['role.min.moderator']);

        $router->post('/search/system-updates', [\App\Controllers\Api\SearchController::class, 'systemUpdates'])
            ->name('api.search.system-updates')
            ->middleware(['role.min.moderator']);
    });

    $router->get('/{any:.*}', function () {
        $response = new \Radix\Http\Response();

        $method = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
            ? $_SERVER['REQUEST_METHOD']
            : '';

        if (strtoupper($method) === 'OPTIONS') {
            $response->setStatusCode(204);
            return $response;
        }

        $response->setStatusCode(404);
        return $response;
    })->name('api.preflight');
});
