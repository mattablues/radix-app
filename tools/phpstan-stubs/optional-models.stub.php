<?php

declare(strict_types=1);

namespace App\Models;

class SystemUpdate
{
    public static function orderBy(string $column, string $direction): self
    {
        return new self();
    }

    public function limit(int $limit): self
    {
        return $this;
    }

    /** @return array<int, mixed> */
    public function get(): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function pluck(string $column): array
    {
        return [];
    }

    public function first(): mixed
    {
        return null;
    }
}
