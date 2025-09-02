<?php

declare(strict_types=1);

$container = require ROOT_PATH . '/config/services.php';

ini_set('session.gc_maxlifetime', '1200'); // 20 minuter
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

date_default_timezone_set(getenv('APP_TIMEZONE'));

set_error_handler('\Radix\Error\RadixErrorHandler::handleError');
set_exception_handler('\Radix\Error\RadixErrorHandler::handleException');

if(getenv('APP_ENV') !== 'development' && is_running_from_console()) {
   http_response_code(403);
   exit('Forbidden: Access is denied.');
}

if (getenv('APP_MAINTENANCE') === '1') {
    throw new \Radix\Http\Exception\MaintenanceException();
}

if (!$container instanceof \Psr\Container\ContainerInterface) {
    throw new RuntimeException("Container verkar vara felaktig: " . get_class($container));
}

$sessionHandler = $container->get(Radix\Session\RadixSessionHandler::class);
session_set_save_handler($sessionHandler, true);

$session = $container->get(\Radix\Session\SessionInterface::class);
$session->start();

setAppContainer($container);

$providers = require ROOT_PATH . '/config/providers.php';

foreach ($providers as $providerClass) {
    $provider = $container->get($providerClass);
    $provider->register();
}

$router = require ROOT_PATH . '/config/routes.php';
$middleware = require ROOT_PATH . '/config/middleware.php';

return $container;