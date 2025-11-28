<?php

declare(strict_types=1);

final readonly class DatabaseSeeder
{
    public function __construct(private \PDO $pdo) {}

    public function run(): void
    {
        (new UsersSeeder($this->pdo))->run();
        (new StatusSeeder($this->pdo))->run();
    }

    public function down(): void
    {
        // Viktigt: ta bort barn först
        (new StatusSeeder($this->pdo))->down();
        (new UsersSeeder($this->pdo))->down();
    }
}
