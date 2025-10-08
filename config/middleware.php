<?php

declare(strict_types=1);

return [
    'auth' => \App\Middlewares\Auth::class,
    'guest' => \App\Middlewares\Guest::class,
    'admin' => \App\Middlewares\Admin::class,
    'private' => \App\Middlewares\PrivateApp::class,
    'location' => \App\Middlewares\Location::class,
];