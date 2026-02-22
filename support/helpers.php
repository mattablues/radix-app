<?php

declare(strict_types=1);

if (!function_exists('route_exists')) {
    function route_exists(string $name): bool
    {
        return array_key_exists($name, \Radix\Routing\Router::routeNames());
    }
}
