<?php

declare(strict_types=1);

final readonly class DatabaseSeeder
{
    public function run(): void
    {
        (new UsersSeeder())->run();
        (new StatusSeeder())->run();
    }

    public function down(): void
    {
        // Viktigt: ta bort barn först
        (new StatusSeeder())->down();
        (new UsersSeeder())->down();
    }
}
