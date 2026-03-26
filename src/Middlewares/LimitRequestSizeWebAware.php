<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class LimitRequestSizeWebAware implements MiddlewareInterface
{
    private const int BYTES_IN_MB = 1048576;

    public function __construct(
        private readonly int $maxBytes = 0
    ) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $maxBytes = $this->maxBytes;

        if ($maxBytes <= 0) {
            $raw = getenv('WEB_MAX_REQUEST_MB');
            $maxMb = filter_var(
                $raw === false ? null : $raw,
                FILTER_VALIDATE_INT,
                ['options' => ['default' => 6]]
            );

            $maxMb = max(1, (int) $maxMb);
            $maxBytes = $maxMb * self::BYTES_IN_MB;
        }

        $value = $request->server['CONTENT_LENGTH'] ?? $request->server['HTTP_CONTENT_LENGTH'] ?? null;
        $length = (is_string($value) && $value !== '' && ctype_digit($value)) ? (int) $value : null;

        if ($length !== null && $length > $maxBytes) {
            if ($this->wantsJson($request)) {
                $res = new Response();
                $res->setStatusCode(413);
                $res->setHeader('Content-Type', 'application/json; charset=utf-8');
                $res->setBody(json_encode([
                    'error' => 'Payload Too Large',
                    'message' => 'Payloaden är för stor.',
                    'max_bytes' => $maxBytes,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
                return $res;
            }

            $request->session()->setFlashMessage('Uppladdningen är för stor. Försök med en mindre bild.', 'error');

            $referer = $request->server['HTTP_REFERER'] ?? null;
            $backUrl = (is_string($referer) && $referer !== '') ? $referer : '/';

            return new RedirectResponse($backUrl);
        }

        return $next->handle($request);
    }

    private function wantsJson(Request $request): bool
    {
        $accept = $request->server['HTTP_ACCEPT'] ?? '';
        $xrw = $request->server['HTTP_X_REQUESTED_WITH'] ?? '';

        $acceptValue = is_string($accept) ? strtolower($accept) : '';
        $xrwValue = is_string($xrw) ? strtolower($xrw) : '';

        return str_contains($acceptValue, 'application/json') || $xrwValue === 'xmlhttprequest';
    }
}
