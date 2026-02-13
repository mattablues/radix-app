<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Radix\Console\Commands\BaseCommand;

final class MakeCommandCommand extends BaseCommand
{
    private const int DIR_MODE = 0o755;

    public function __construct(
        private readonly string $commandsBasePath,
        private readonly string $templatePath,
        private readonly string $configCommandsFile,
    ) {}

    /**
     * @param array<int, string> $args
     */
    public function execute(array $args): void
    {
        $this->__invoke($args);
    }

    /**
     * @param array<int|string, string> $args
     */
    public function __invoke(array $args): void
    {
        $usage = 'make:command <ClassName> [--command=users:sync] [--no-config]';
        $options = [
            '<ClassName>' => 'Command class name, e.g. UsersSyncCommand (suffix Command is recommended).',
            '--command=users:sync' => 'CLI command name to register (default derived from class name).',
            '--no-config' => 'Do not update config/commands.php automatically.',
            '--help, -h' => 'Display this help message.',
            '--md, --markdown' => 'Output help as Markdown.',
        ];
        $examples = [
            'make:command UsersSyncCommand',
            'make:command HealthCheckCommand --command=app:health',
            'make:command FooCommand --no-config',
        ];

        $argv = array_filter($args, static fn($k): bool => is_int($k), ARRAY_FILTER_USE_KEY);
        /** @var array<int,string> $argv */

        if ($this->handleHelpFlag($argv, $usage, $options, $examples)) {
            return;
        }

        $className = $this->firstArgValue($args);
        if ($className === null) {
            $this->coloredOutput("Error: <ClassName> is required.", 'red');
            $this->coloredOutput("Tip: php radix make:command --help", 'yellow');
            return;
        }

        if (!preg_match('/\A[A-Z][A-Za-z0-9_]*\z/', $className)) {
            $this->coloredOutput("Error: Invalid class name '{$className}'. Use PascalCase, e.g. UsersSyncCommand.", 'red');
            return;
        }

        $opts = $this->parseOptions($args);
        $cliCommand = $opts['command'] ?? $this->deriveCliNameFromClass($className);
        $noConfig = array_key_exists('no-config', $opts);

        if ($cliCommand === '') {
            $this->coloredOutput("Error: --command must be a non-empty string.", 'red');
            return;
        }

        $targetDir = rtrim($this->commandsBasePath, '/\\');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, self::DIR_MODE, true);
        }

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $className . '.php';
        if (is_file($targetFile)) {
            $this->coloredOutput("Error: Command already exists: {$targetFile}", 'red');
            return;
        }

        $stubFile = rtrim($this->templatePath, '/\\') . DIRECTORY_SEPARATOR . 'command.stub';
        if (!is_file($stubFile)) {
            $this->coloredOutput("Error: Stub not found: {$stubFile}", 'red');
            return;
        }

        $stub = file_get_contents($stubFile);
        if ($stub === false) {
            $this->coloredOutput("Error: Failed reading stub: {$stubFile}", 'red');
            return;
        }

        if (!str_contains($stub, 'function execute(')) {
            $this->coloredOutput("Error: Invalid stub. Missing method 'execute(array \$args): void' in: {$stubFile}", 'red');
            $this->coloredOutput("Fix: add execute() that delegates to __invoke().", 'yellow');
            return;
        }

        $namespace = 'App\\Console\\Commands';

        $content = str_replace(
            ['[NAMESPACE]', '[CLASS]', '[CLI_COMMAND]'],
            [$namespace, $className, $cliCommand],
            $stub
        );

        if (file_put_contents($targetFile, $content) === false) {
            $this->coloredOutput("Error: Failed writing file: {$targetFile}", 'red');
            return;
        }

        $this->coloredOutput("Command created: {$targetFile}", 'green');

        if ($noConfig) {
            $this->coloredOutput("Skipped config update (--no-config). Register manually in config/commands.php.", 'yellow');
            return;
        }

        $this->tryAppendToCommandsConfig($cliCommand, $namespace . '\\' . $className);
    }

    /**
     * @param array<int|string, string> $args
     */
    private function firstArgValue(array $args): ?string
    {
        foreach ($args as $k => $v) {
            if (!is_int($k)) {
                continue;
            }
            if ($v === '' || str_starts_with($v, '-')) {
                continue;
            }
            return $v;
        }

        return null;
    }

    /**
     * @param array<int|string, string> $args
     * @return array<string,string>
     */
    private function parseOptions(array $args): array
    {
        $opts = [];

        foreach ($args as $arg) {
            if (!is_string($arg)) {
                continue;
            }
            if (!str_starts_with($arg, '--')) {
                continue;
            }

            $raw = substr($arg, 2);
            if ($raw === '') {
                continue;
            }

            if (str_contains($raw, '=')) {
                [$k, $v] = explode('=', $raw, 2);
                $k = trim($k);
                if ($k !== '') {
                    $opts[$k] = $v;
                }
                continue;
            }

            $opts[$raw] = '1';
        }

        return $opts;
    }

    private function deriveCliNameFromClass(string $className): string
    {
        $base = $className;
        if (str_ends_with($base, 'Command')) {
            $base = substr($base, 0, -7);
        }

        // Split PascalCase: UsersSync -> ['Users','Sync']
        preg_match_all('/[A-Z][a-z0-9]*/', $base, $m);
        $parts = $m[0];

        if ($parts === []) {
            return strtolower($base);
        }

        $parts = array_map(static fn(string $p): string => strtolower($p), $parts);

        // 1 word: "cache" -> "cache"
        if (count($parts) === 1) {
            return $parts[0];
        }

        // 2+ words: "users:sync-extra"
        $group = array_shift($parts);
        $rest = implode('-', $parts);

        return $group . ':' . $rest;
    }

    private function tryAppendToCommandsConfig(string $cliCommand, string $fqcn): void
    {
        $file = $this->configCommandsFile;

        if (!is_file($file)) {
            $this->coloredOutput("Warning: Config file not found: {$file}", 'yellow');
            $this->coloredOutput("Register manually: '{$cliCommand}' => \\{$fqcn}::class", 'yellow');
            return;
        }

        $src = file_get_contents($file);
        if ($src === false) {
            $this->coloredOutput("Warning: Failed reading config file: {$file}", 'yellow');
            return;
        }

        // Already registered?
        if (str_contains($src, "'" . $cliCommand . "'")) {
            $this->coloredOutput("Config already contains '{$cliCommand}'. Skipping update.", 'yellow');
            return;
        }

        // Insert after: 'commands' => [
        $needle = "'commands' => [";
        $pos = strpos($src, $needle);
        if ($pos === false) {
            $this->coloredOutput("Warning: Could not find {$needle} in {$file}. Register manually.", 'yellow');
            return;
        }

        $insertPos = $pos + strlen($needle);

        // Viktigt: i en PHP-fil vill vi ha EN ledande "\" i klass-referensen.
        $line = "\n        '{$cliCommand}' => \\{$fqcn}::class,";

        $out = substr($src, 0, $insertPos) . $line . substr($src, $insertPos);

        if (file_put_contents($file, $out) === false) {
            $this->coloredOutput("Warning: Failed writing config file: {$file}", 'yellow');
            return;
        }

        $this->coloredOutput("Registered in config: {$cliCommand}", 'green');
    }
}
