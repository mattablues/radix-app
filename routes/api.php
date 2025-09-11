<?php

declare(strict_types=1);

/** @var $router \Radix\Routing\Router */

// API-rutter med versionering
$router->group(['path' => '/api/v1'], function (\Radix\Routing\Router $router) {
//    // Route för att hämta alla användare
    $router->get('/users', [\App\Controllers\Api\UserController::class, 'index'])
        ->name('api.users.index');
//
//    // Route för att skapa en ny användare
//    $router->post('/users', [\App\Controllers\Api\UserController::class, 'store'])
//        ->name('api.users.store');
//
//    // Route för att uppdatera en användares alla fält (PUT)
//    $router->put('/users/{id}', [\App\Controllers\Api\UserController::class, 'update'])
//        ->name('api.users.update');
//
//    // Route för att delvis uppdatera en användare (PATCH)
//    $router->patch('/users/{id}', [\App\Controllers\Api\UserController::class, 'partialUpdate'])
//        ->name('api.users.partialUpdate');
//
//    // Route för att radera en användare (DELETE)
//    $router->delete('/users/{id}', [\App\Controllers\Api\UserController::class, 'delete'])
//        ->name('api.users.delete');

    $router->post('/search/users', [\App\Controllers\Api\SearchController::class, 'users'])
           ->name('api.search.users');
});

