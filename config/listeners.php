<?php

declare(strict_types=1);

return [
    \App\Events\ContactFormEvent::class => [
        [
            'listener' => \App\EventListeners\SendContactEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 5, // Standardprioritet
        ],
    ],
];
