<?php

declare(strict_types=1);

namespace App\Support;

final class AppPaths
{
    public function commandsDir(): string
    {
        return ROOT_PATH . '/src/Console/Commands';
    }

    public function templatesDir(): string
    {
        return ROOT_PATH . '/templates';
    }

    public function commandsConfigFile(): string
    {
        return ROOT_PATH . '/config/commands.php';
    }

    public function ensureDir(string $dir, int $mode = 0o755): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, true);
        }
    }
}
