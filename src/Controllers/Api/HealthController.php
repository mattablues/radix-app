<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

final class HealthController extends ApiController
{
    public function index(): JsonResponse
    {
        $ok = true;
        $checks = [
            'php' => PHP_VERSION,
            'time' => date('c'),
        ];

        try {
            if (function_exists('app')) {
                $dbm = app(\Radix\Database\DatabaseManager::class);
                $conn = $dbm->connection();
                $conn->execute('SELECT 1');
                $checks['db'] = 'ok';
            } else {
                $checks['db'] = 'skipped';
            }
        } catch (\Throwable $e) {
            $ok = false;
            $checks['db'] = 'fail: ' . $e->getMessage();
        }

        try {
            $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $dir = rtrim($root, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'health';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $probe = $dir . DIRECTORY_SEPARATOR . 'probe.txt';
            if (@file_put_contents($probe, (string) time()) === false) {
                throw new \RuntimeException('file_put_contents failed');
            }
            @unlink($probe);
            $checks['fs'] = 'ok';
        } catch (\Throwable $e) {
            $ok = false;
            $checks['fs'] = 'fail: ' . $e->getMessage();
        }

        $res = new JsonResponse();
        $res->setStatusCode($ok ? 200 : 500);
        $res->setHeader('Content-Type', 'application/json; charset=utf-8');
        $res->setBody(json_encode(['ok' => $ok, 'checks' => $checks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $res;
    }
}