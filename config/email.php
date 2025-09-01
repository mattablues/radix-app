<?php

declare(strict_types=1);

return [
    'email' => [
        'debug' => getenv('MAIL_DEBUG') ?? '0',
        'charset' => getenv('MAIL_CHARSET') ?? 'UTF-8',
        'host' => getenv('MAIL_HOST') ?? '',
        'port' => getenv('MAIL_PORT') ?? '',
        'secure' => getenv('MAIL_SECURE') ?? 'tls',
        'auth' => filter_var(getenv('MAIL_AUTH') ?? true, FILTER_VALIDATE_BOOLEAN),
        'username' => getenv('MAIL_ACCOUNT') ?? '',
        'password' => getenv('MAIL_PASSWORD') ?? '',
        'email' => getenv('MAIL_EMAIL') ?? 'noreply@example.com', // <== Fall back to a default address
        'from' => getenv('MAIL_FROM') ?? 'No Reply', // <== Fall back to a default name
    ]
];
