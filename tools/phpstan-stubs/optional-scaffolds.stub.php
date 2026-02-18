<?php

declare(strict_types=1);

namespace App\Services;

final class HealthCheckService
{
    public function __construct(mixed $logger) {}
}

final class ProfileAvatarService
{
    public function __construct(mixed $uploadService) {}
}

final class AuthService
{
    public function __construct(mixed $session) {}
}
