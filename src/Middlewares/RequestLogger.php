<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Support\Logger;

final readonly class RequestLogger implements MiddlewareInterface
{
    public function __construct(
        private Logger $logger,
        private string $channel = 'http'
    ) {}

    private static function usFromDeltaSeconds(float $deltaSeconds): int
    {
        // Korrekt avrundning – ska vara +0.5
        return (int) (($deltaSeconds * 1_000_000.0) + 0.5);
    }

    private static function msFromUs(int $us): int
    {
        // Exakt ms-formel – måste vara intdiv(us + 500, 1000) och (int)-cast kvar
        return (int) intdiv($us + 500, 1000);
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $start = microtime(true);

        $reqId = $request->header('X-Request-Id') ?? '';
        $ip = $request->ip();
        $method = $request->method;
        $path = $request->uri;
        $ua = $request->header('User-Agent') ?? '';
        $session = $request->session();
        $authKeyConst = \Radix\Session\Session::AUTH_KEY;
        $userId = $session->get($authKeyConst);

        try {
            $response = $next->handle($request);
            return $response;
        } finally {
            $end = microtime(true);
            $delta = $end - $start;

            $us = self::usFromDeltaSeconds($delta);

            // Monoton clamp på us med strikt < (inte <=)
            static $lastUs = null;
            if ($lastUs !== null && $us < $lastUs) {
                $us = $lastUs;
            } else {
                $lastUs = $us;
            }

            /** @var int $us */
            $us = $us;

            $ms = self::msFromUs($us);

            $status = isset($response) ? $response->getStatusCode() : 500;

            $this->logger->info(
                '{method} {path} -> {status} {ms}ms',
                [
                    'method' => $method,
                    'path' => $path,
                    'status' => $status,
                    'ms' => $ms,
                    'us' => $us,
                    'ip' => $ip,
                    'ua' => $ua,
                    'userId' => is_int($userId) ? $userId : null,
                    'requestId' => $reqId !== '' ? $reqId : null,
                    'channel' => $this->channel,
                ]
            );
        }
    }
}
