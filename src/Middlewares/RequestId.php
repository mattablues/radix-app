<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class RequestId implements MiddlewareInterface
{
    public function process(Request $request, \Radix\Http\RequestHandlerInterface $next): Response
    {
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(12));
        $_SERVER['HTTP_X_REQUEST_ID'] = $requestId;

        $start = microtime(true);

        $response = $next->handle($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $response->setHeader('X-Request-Id', $requestId);
        $response->setHeader('X-Response-Time', (string) $durationMs . 'ms');

        $method = $_SERVER['REQUEST_METHOD'] ?? $request->method ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? $request->uri ?? '/';
        $status = $response->getStatusCode();
        error_log(sprintf('[%s] %s %s %d %dms', $requestId, $method, $uri, $status, $durationMs));

        return $response;
    }
}