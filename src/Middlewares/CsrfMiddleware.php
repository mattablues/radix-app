<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Error\ErrorResponder;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $method = strtoupper($request->method);

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $tokenFromForm = $request->getCsrfToken();
            $tokenFromHeader = $request->header('X-CSRF-Token');

            $provided = ($tokenFromForm !== null && $tokenFromForm !== '') ? $tokenFromForm
                : ($tokenFromHeader ?? '');

            $sessionToken = $request->session()->csrf();

            if ($provided === '' || !hash_equals($sessionToken, $provided)) {
                return ErrorResponder::respond($request, 419, 'Page Expired / CSRF token mismatch');
            }
        }

        return $next->handle($request);
    }
}