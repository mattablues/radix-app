<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

class UserRegisteredEvent extends Event
{
    public function __construct(
        public readonly string $email,
        public readonly string $activationLink,
        public readonly ?string $password = null
    ) {}
}