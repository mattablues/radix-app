<?php

declare(strict_types=1);

/** @var \Radix\Routing\Router $router */
/** @var \Psr\Container\ContainerInterface $container */
/** @var array<callable> $middleware */

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/bootstrap/app.php';

$dispatcher = new \Radix\Routing\Dispatcher($router, $container, $middleware);
$request = \Radix\Http\Request::createFromGlobals();

$response = $dispatcher->handle($request);
$response->send();