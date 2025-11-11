<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class HealthWebController extends AbstractController
{
    public function index(): Response
    {
        $service = new \App\Services\HealthCheckService();
        $checks = $service->run();
        $ok = (bool)($checks['_ok'] ?? false);

        return $this->view('health.index', [
            'checks' => $checks,
            'ok' => $ok,
        ]);
    }
}