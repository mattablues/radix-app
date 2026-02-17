<?php

declare(strict_types=1);

final readonly class DatabaseSeeder
{
    public function run(): void
    {
        // Core seeders (ska vara minimal by default)
        // (new UsersSeeder())->run();

        // Optional scaffolded seeders (install = filen finns)
        foreach (['user', 'updates', 'admin', 'auth'] as $preset) {
            $optional = __DIR__ . '/' . $preset . '.seeders.php';

            if (!is_file($optional)) {
                continue;
            }

            /** @phpstan-ignore-next-line require.fileNotFound optional scaffolded file */
            $seeders = require $optional;

            if (!is_array($seeders)) {
                continue;
            }

            foreach ($seeders as $seederClass) {
                if (is_string($seederClass) && class_exists($seederClass)) {
                    (new $seederClass())->run();
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['auth', 'admin', 'updates', 'user'] as $preset) {
            $optional = __DIR__ . '/' . $preset . '.seeders.php';

            if (!is_file($optional)) {
                continue;
            }

            /** @phpstan-ignore-next-line require.fileNotFound optional scaffolded file */
            $seeders = require $optional;

            if (!is_array($seeders)) {
                continue;
            }

            foreach (array_reverse($seeders) as $seederClass) {
                if (is_string($seederClass) && class_exists($seederClass)) {
                    $seeder = new $seederClass();
                    if (method_exists($seeder, 'down')) {
                        $seeder->down();
                    }
                }
            }
        }

        // Core down (barn först)
        // (new UsersSeeder())->down();
    }
}
