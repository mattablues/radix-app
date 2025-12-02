<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

// Toppgrupp: request-id + logging på hela API:t (ingen throttle här)
$router->group(['path' => '/api/v1', 'middleware' => ['request.id', 'api.logger']], function (\Radix\Routing\Router $router) {
    // Health: endast allowlist, ingen throttling
    $router->get('/health', [\App\Controllers\Api\HealthController::class, 'index'])
        ->name('api.health.index')
        ->middleware(['ip.allowlist']);

    // Undergrupp: throttling på resten (standardpolicy)
    $router->group(['middleware' => ['api.throttle']], function (\Radix\Routing\Router $router) {
        // Route för att hämta alla användare
        $router->get('/users', [\App\Controllers\Api\UserController::class, 'index'])
            ->name('api.users.index');

        // Route för att skapa en ny användare
        //        $router->post('/users', [\App\Controllers\Api\UserController::class, 'store'])
        //            ->name('api.users.store');
        //
        //        // Route för att uppdatera en användares alla fält (PUT)
        //        $router->put('/users/{id}', [\App\Controllers\Api\UserController::class, 'update'])
        //            ->name('api.users.update');
        //
        //        // Route för att delvis uppdatera en användare (PATCH)
        //        $router->patch('/users/{id}', [\App\Controllers\Api\UserController::class, 'partialUpdate'])
        //            ->name('api.users.partialUpdate');
        //
        //        // Route för att radera en användare (DELETE)
        //        $router->delete('/users/{id}', [\App\Controllers\Api\UserController::class, 'delete'])
        //            ->name('api.users.delete');
        //
        //        // Sök-endpoints
        $router->post('/search/users', [\App\Controllers\Api\SearchController::class, 'users'])
               ->name('api.search.users');

        $router->post('/search/deleted-users', [\App\Controllers\Api\SearchController::class, 'deletedUsers'])
           ->name('api.search.deleted-users');
    });

    // Preflight (OPTIONS mappas till GET i Dispatcher): returnera 204 endast om originalmetoden var OPTIONS
    $router->get('/{any:.*}', function () {
        $response = new \Radix\Http\Response();

        // Säkerställ att vi inte stjäl riktiga GET-rutter: svara 204 endast för preflight (OPTIONS)
        $method = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
            ? $_SERVER['REQUEST_METHOD']
            : '';

        if (strtoupper($method) === 'OPTIONS') {
            $response->setStatusCode(204);
            return $response;
        }

        // För andra metoder (t.ex. GET) låt detta falla igenom som 404
        $response->setStatusCode(404);
        return $response;
    })->name('api.preflight');
});
