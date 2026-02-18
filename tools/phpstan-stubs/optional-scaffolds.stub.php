<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class HealthCheckService
{
    public function __construct(mixed $logger) {}

    /**
     * @return array<string, string|bool>
     */
    public function run(): array {}
}

final class ProfileAvatarService
{
    public function __construct(mixed $uploadService) {}

    /**
     * @param int|string $userId
     * @param array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null $avatar
     */
    public function updateAvatar(User $user, int|string $userId, ?array $avatar): void {}
}

final class AuthService
{
    public function __construct(mixed $session) {}

    public function isBlocked(string $email): bool {}

    public function getBlockedUntil(string $email): ?int {}

    public function isIpBlocked(string $ip): bool {}

    public function getBlockedIpUntil(string $ip): ?int {}

    public function clearFailedAttempts(string $email, bool $removeBlocked = true): void {}

    public function clearFailedIpAttempt(string $ip): void {}

    public function trackFailedAttempt(string $email): void {}

    public function trackFailedIpAttempt(string $ip): void {}

    /**
     * @param array{email:string,password:string} $data
     */
    public function login(array $data): ?User {}

    public function getStatusError(?User $user): ?string {}
}
