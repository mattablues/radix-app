<?php

declare(strict_types=1);

namespace App\Services;

final class HealthCheckService
{
    public function run(): array
    {
        $ok = true;
        $checks = [
            'php' => PHP_VERSION,
            'time' => date('c'),
        ];

        $this->log('[health] start php=' . $checks['php'] . ' time=' . $checks['time']);

        // DB
        try {
            if (function_exists('app')) {
                $dbm = app(\Radix\Database\DatabaseManager::class);
                $conn = $dbm->connection();
                $conn->execute('SELECT 1');
                $checks['db'] = 'ok';
                $this->log('[health] db=ok');
            } else {
                $checks['db'] = 'skipped';
                $this->log('[health] db=skipped (no app())');
            }
        } catch (\Throwable $e) {
            $ok = false;
            $checks['db'] = 'fail: ' . $e->getMessage();
            $this->log('[health] db=fail msg=' . $e->getMessage());
        }

        // FS
        try {
            $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $dir = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'health';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
                $this->log('[health] created_dir ' . $dir);
            }
            $probe = $dir . DIRECTORY_SEPARATOR . 'probe.txt';
            if (@file_put_contents($probe, (string) time()) === false) {
                throw new \RuntimeException('file_put_contents failed');
            }
            @unlink($probe);
            $checks['fs'] = 'ok';
            $this->log('[health] fs=ok dir=' . $dir);
        } catch (\Throwable $e) {
            $ok = false;
            $checks['fs'] = 'fail: ' . $e->getMessage();
            $this->log('[health] fs=fail msg=' . $e->getMessage());
        }

        $checks['_ok'] = $ok;
        return $checks;
    }

    private function log(string $msg): void
    {
        error_log($msg);
    }
}