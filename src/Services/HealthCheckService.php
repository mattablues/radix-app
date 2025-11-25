<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use Throwable;

class HealthCheckService
{
    private \Radix\Support\Logger $logger;

    public function __construct(?\Radix\Support\Logger $logger = null)
    {
        // Tillåt DI, fall back om ej satt
        $this->logger = $logger ?? new \Radix\Support\Logger('health');
    }

    /**
     * @return array<string, string|bool>
     */
    public function run(): array
    {
        $ok = true;
        $checks = [
            'php' => PHP_VERSION,
            'time' => date('c'),
        ];

        $this->log('start php={php} time={time}', ['php' => $checks['php'], 'time' => $checks['time']]);

        // DB
        try {
            if (function_exists('app')) {
                /** @var \Radix\Database\DatabaseManager $dbm */
                $dbm = app(\Radix\Database\DatabaseManager::class);
                $conn = $dbm->connection();
                $conn->execute('SELECT 1');
                $checks['db'] = 'ok';
                $this->log('db=ok');
            } else {
                $checks['db'] = 'skipped';
            }
        } catch (Throwable $e) {
            $ok = false;
            $checks['db'] = 'fail: ' . $e->getMessage();
            $this->logError('db=fail msg={msg}', ['msg' => $e->getMessage()]);
        }

        // FS
        try {
            $dir = getenv('HEALTH_CACHE_PATH');

            // Strikt validering (immun mot LogicalOr-mutation)
            if (!is_string($dir)) {
                throw new RuntimeException('HEALTH_CACHE_PATH must be set to an absolute directory.');
            }
            if ($dir === '') {
                throw new RuntimeException('HEALTH_CACHE_PATH must be set to an absolute directory.');
            }

            if (!is_dir($dir)) {
                @mkdir($dir, 0o755, true);
                $this->log('created_dir {dir}', ['dir' => $dir]);
            }

            $base = realpath($dir) ?: $dir;
            $probe = $base . DIRECTORY_SEPARATOR . 'probe.txt';
            if (@file_put_contents($probe, (string) time()) === false) {
                throw new RuntimeException('file_put_contents failed');
            }
            @unlink($probe);

            $checks['fs'] = 'ok';
            $this->log('fs=ok dir={dir}', ['dir' => $base]);
        } catch (Throwable $e) {
            $ok = false;
            $checks['fs'] = 'fail: ' . $e->getMessage();
            $this->logError('fs=fail msg={msg}', ['msg' => $e->getMessage()]);
        }

        $checks['_ok'] = $ok;
        return $checks;
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function log(string $msg, array $ctx = []): void
    {
        $this->logger->info($msg, $ctx);
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function logError(string $msg, array $ctx = []): void
    {
        $this->logger->error($msg, $ctx);
    }
}
