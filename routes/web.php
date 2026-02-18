<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

// Global grupp för web med request-id + logging

$router->group(['middleware' => ['request.id', 'api.logger', 'security.headers', 'limit.2mb', 'csrf']], function () use ($router) {
    $router->get('/', [
        \App\Controllers\HomeController::class, 'index',
    ])->name('home.index');

    $router->get('/contact', [
        \App\Controllers\ContactController::class, 'index',
    ])->name('contact.index');

    $router->get('/about', [
        \App\Controllers\AboutController::class, 'index',
    ])->name('about.index');

    // Throttle bara POST till kontaktformuläret
    $router->post('/contact', [
        \App\Controllers\ContactController::class, 'create',
    ])->name('contact.create')->middleware(['api.throttle.light']);

    $router->get('/cookie', [
        \App\Controllers\CookieController::class, 'index',
    ])->name('cookie.index');
});
