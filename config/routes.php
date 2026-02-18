<?php

declare(strict_types=1);

use Radix\Routing\Router;

$router = new Router();

require ROOT_PATH . '/routes/web.php';
require ROOT_PATH . '/routes/api.php';

// Auth/User/Admin blir "minimal by default" – laddas bara om filerna finns
foreach ([
    ROOT_PATH . '/routes/auth.php',
    ROOT_PATH . '/routes/user.php',
    ROOT_PATH . '/routes/admin.php',
    ROOT_PATH . '/routes/updates.php',
] as $optionalRouteFile) {
    if (is_file($optionalRouteFile)) {
        require $optionalRouteFile;
    }
}

return $router;
