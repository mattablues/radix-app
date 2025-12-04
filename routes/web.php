<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */

// Global grupp för web med request-id + logging
$router->group(['middleware' => ['request.id', 'api.logger','csrf']], function () use ($router) {
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

    $router->group(['middleware' => ['private', 'guest']], function () use ($router) {
        $router->get('/register', [
            \App\Controllers\Auth\RegisterController::class, 'index',
        ])->name('auth.register.index');

        // Throttle registrering
        $router->post('/register', [
            \App\Controllers\Auth\RegisterController::class, 'create',
        ])->name('auth.register.create')->middleware(['api.throttle.light']);
    });

    $router->group(['middleware' => ['guest']], function () use ($router) {
        $router->get('/register/activate/{token:[\da-f]+}', [
            \App\Controllers\Auth\RegisterController::class, 'activate',
        ])->name('auth.register.activate');

        $router->get('/login', [
            \App\Controllers\Auth\LoginController::class, 'index',
        ])->name('auth.login.index');

        // Throttle login
        $router->post('/login', [
            \App\Controllers\Auth\LoginController::class, 'create',
        ])->name('auth.login.create')->middleware(['api.throttle.hard']);

        $router->get('/password-forgot', [
            \App\Controllers\Auth\PasswordForgotController::class, 'index',
        ])->name('auth.password-forgot.index');

        // Throttle password-forgot
        $router->post('/password-forgot', [
            \App\Controllers\Auth\PasswordForgotController::class, 'create',
        ])->name('auth.password-forgot.create')->middleware(['api.throttle.light']);

        $router->get('/password-reset/{token:[\da-f]+}', [
            \App\Controllers\Auth\PasswordResetController::class, 'index',
        ])->name('auth.password-reset.index');

        // Throttle password-reset
        $router->post('/password-reset/{token:[\da-f]+}', [
            \App\Controllers\Auth\PasswordResetController::class, 'create',
        ])->name('auth.password-reset.create')->middleware(['api.throttle.light']);

        $router->get('/logout-message', [
            \App\Controllers\Auth\LogoutController::class, 'logoutMessage',
        ])->name('auth.logout.message');

        $router->get('/logout-close-message', [
            \App\Controllers\Auth\LogoutController::class, 'closeLogoutMessage',
        ])->name('auth.logout.close-message');

        $router->get('/logout-delete-message', [
            \App\Controllers\Auth\LogoutController::class, 'deletedLogoutMessage',
        ])->name('auth.logout.delete-message');

        $router->get('/logout-blocked-message', [
            \App\Controllers\Auth\LogoutController::class, 'blockedLogoutMessage',
        ])->name('auth.logout.blocked-message');
    });

    $router->group(['middleware' => ['auth']], function () use ($router) {
        $router->get('/dashboard', [
            \App\Controllers\Dashboard::class, 'index',
        ])->name('dashboard.index');

        $router->get('/user', [
            \App\Controllers\UserController::class, 'index',
        ])->name('user.index');

        $router->get('/user/{id:[\d]+}/show', [
            \App\Controllers\UserController::class, 'show',
        ])->name('user.show');

        $router->get('/user/edit', [
            \App\Controllers\UserController::class, 'edit',
        ])->name('user.edit');

        // Throttle känsliga POST endpoints
        $router->post('/user/edit', [
            \App\Controllers\UserController::class, 'update',
        ])->name('user.update')->middleware(['api.throttle.light']);

        $router->post('/user/delete', [
            \App\Controllers\UserController::class, 'delete',
        ])->name('user.delete')->middleware(['api.throttle.hard']);

        $router->post('/user/close', [
            \App\Controllers\UserController::class, 'close',
        ])->name('user.close')->middleware(['api.throttle.hard']);

        $router->post('/logout', [
            \App\Controllers\Auth\LogoutController::class, 'index',
        ])->name('auth.logout.index');
    });

    $router->group(['path' => '/admin', 'middleware' => ['auth', 'role.exact.admin']], function () use ($router) {
        $router->get('/users/create-user', [
            \App\Controllers\Admin\UserController::class, 'create',
        ])->name('admin.user.create');

        $router->post('/users/create-user', [
            \App\Controllers\Admin\UserController::class, 'store',
        ])->name('admin.user.store')->middleware(['api.throttle.light']);

        $router->post('/users/{id:[\d]+}/role', [
            \App\Controllers\Admin\UserController::class, 'role',
        ])->name('admin.user.role')->middleware(['api.throttle.light']);
    });

    $router->group(['path' => '/admin', 'middleware' => ['auth', 'role.min.moderator']], function () use ($router) {
        $router->get('/users', [
            \App\Controllers\Admin\UserController::class, 'index',
        ])->name('admin.user.index');

        $router->post('/users/{id:[\d]+}/send-activation', [
            \App\Controllers\Admin\UserController::class, 'sendActivation',
        ])->name('admin.user.send-activation')->middleware(['api.throttle.light']);

        $router->post('/users/{id:[\d]+}/block', [
            \App\Controllers\Admin\UserController::class, 'block',
        ])->name('admin.user.block')->middleware(['api.throttle.light']);

        $router->get('/users/closed', [
            \App\Controllers\Admin\UserController::class, 'closed',
        ])->name('admin.user.closed');

        $router->post('/users/{id:[\d]+}/restore', [
            \App\Controllers\Admin\UserController::class, 'restore',
        ])->name('admin.user.restore')->middleware(['api.throttle.light']);

        $router->get('/health', [
            \App\Controllers\Admin\HealthWebController::class, 'index',
        ])->name('admin.health.index');
    });
});
