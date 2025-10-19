<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';

    public function level(): int
    {
        return match ($this) {
            self::User => 10,
            self::Admin => 50,
        };
    }

    public static function tryFromName(string $role): ?self
    {
        return self::tryFrom($role);
    }
}
