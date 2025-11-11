<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

final class HealthController extends ApiController
{
    public function __construct(private readonly \App\Services\HealthCheckService $health)
    {
    }

    public function index(): JsonResponse
    {
        $start = microtime(true);

        $checks = $this->health->run();
        $ok = (bool)($checks['_ok'] ?? false);
        unset($checks['_ok']);

        $res = new JsonResponse();
        $res->setStatusCode($ok ? 200 : 500);
        $res->setHeader('Content-Type', 'application/json; charset=utf-8');
        $res->setBody(json_encode(['ok' => $ok, 'checks' => $checks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $ms = (int) round((microtime(true) - $start) * 1000);
        // valfri loggning här via logger i HealthCheckService

        return $res;
    }
}