<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
    ],
], function (\Radix\Routing\Router $router) {
    // Admin-scaffold routes (t.ex. /health)
    $adminRoutes = __DIR__ . '/api.admin.php';
    if (is_file($adminRoutes)) {
        require $adminRoutes;
    }

    // User-scaffold routes (t.ex. /users + /search/*)
    $userRoutes = __DIR__ . '/api.user.php';
    if (is_file($userRoutes)) {
        require $userRoutes;
    }

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
