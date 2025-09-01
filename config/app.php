<?php

declare(strict_types=1);

return [
    'app' => [
        'app_env' => getenv('APP_ENV') ?: 'production',
        'app_lang' => getenv('APP_LANG') ?: 'en',
        'app_name' => getenv('APP_NAME') ?: 'Your App Name',
        'app_copy' => getenv('APP_COPY') ?: 'Your Copyright',
        'app_timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'app_url' => getenv('APP_URL') ?: 'http://localhost',
        'app_maintenance' => getenv('APP_MAINTENANCE') ?: '0',
        'app_debug' => getenv('APP_DEBUG') ?: '0',
    ],
];