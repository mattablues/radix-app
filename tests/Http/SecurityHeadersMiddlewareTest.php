<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Config\Config;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\Middlewares\SecurityHeaders;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    /**
     * Skapar en middleware med en Config‑mock som returnerar given CSP‑array.
     *
     * @param array<string, mixed> $cspConfig
     */
    private function makeMiddleware(array $cspConfig): SecurityHeaders
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('csp', [])
            ->willReturn($cspConfig);

        return new SecurityHeaders($config);
    }

    private function makeHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                $res = new Response();
                $res->setStatusCode(200);
                $res->setBody('OK');
                return $res;
            }
        };
    }

    public function testWebContextSetsCspHeaderWithNonceAndOtherSecurityHeaders(): void
    {
        $cspConfig = [
            'web' => [
                'default-src' => ["'self'"],
                'style-src'   => ["'self'", "'unsafe-inline'"],
                'script-src'  => [
                    "'self'",
                    static function (): string {
                        return "'nonce-TESTNONCE'";
                    },
                ],
                'img-src'     => ["'self'", 'data:'],
                'font-src'    => ["'self'", 'data:'],
                'connect-src' => ["'self'", 'http://localhost:5173'],
                'frame-ancestors' => ["'none'"],
            ],
            'api' => [
                'default-src' => ["'none'"],
                'frame-ancestors' => ["'none'"],
            ],
            'enable_hsts' => true,
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'REQUEST_SCHEME' => 'https',
            ]
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-TESTNONCE'", $csp);
        $this->assertStringContainsString("img-src 'self' data:", $csp);
        $this->assertStringContainsString("font-src 'self' data:", $csp);
        $this->assertStringContainsString("connect-src 'self' http://localhost:5173", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        // Övriga säkerhetsheaders
        $this->assertSame('nosniff', $headers['X-Content-Type-Options'] ?? null);
        $this->assertSame('DENY', $headers['X-Frame-Options'] ?? null);
        $this->assertSame('strict-origin-when-cross-origin', $headers['Referrer-Policy'] ?? null);
        $this->assertSame('0', $headers['X-XSS-Protection'] ?? null);

        // HSTS ska vara satt eftersom request är https och enable_hsts = true
        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
        $this->assertSame(
            'max-age=31536000; includeSubDomains; preload',
            $headers['Strict-Transport-Security']
        );
    }

    public function testApiContextUsesApiCspProfile(): void
    {
        $cspConfig = [
            'web' => [
                'default-src' => ["'self'"],
            ],
            'api' => [
                'default-src' => ["'none'"],
                'frame-ancestors' => ["'none'"],
            ],
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/api/v1/users',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        // Viktigt: web‑profilen ska inte läcka in
        $this->assertStringNotContainsString("default-src 'self'", $csp);
    }

    public function testHstsNotSetForNonHttpsRequest(): void
    {
        $cspConfig = [
            'web' => [
                'default-src' => ["'self'"],
            ],
            'enable_hsts' => true,
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'REQUEST_SCHEME' => 'http',
            ]
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();

        $this->assertArrayNotHasKey('Strict-Transport-Security', $headers);
    }

    public function testInvalidCspConfigForContextIsIgnoredGracefully(): void
    {
        // web-profilen finns, men är FEL typ (sträng istället för array).
        // Originalkod: isset == true, is_array == false → hoppar över utan krasch.
        // Mutant med &&→|| kommer försöka skicka en sträng till buildCspHeader (TypeError).
        $cspConfig = [
            'web' => 'NOT_AN_ARRAY',
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'REQUEST_SCHEME' => 'https',
            ]
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();
        // Vi förväntar oss inga CSP-headers, men framför allt att det INTE kraschar.
        $this->assertArrayNotHasKey('Content-Security-Policy', $headers);
    }

    public function testHttpsDetectedFromForwardedProto(): void
    {
        $cspConfig = [
            'web' => [
                'default-src' => ["'self'"],
            ],
            'enable_hsts' => true,
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ]
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();

        $this->assertArrayHasKey('Strict-Transport-Security', $headers);
        $this->assertSame(
            'max-age=31536000; includeSubDomains; preload',
            $headers['Strict-Transport-Security']
        );
    }

    public function testInvalidDirectiveNamesAreSkipped(): void
    {
        // Direktiven "1foo" och "foo1" bryter mot /^[a-z\-]+$/ och ska ignoreras.
        // "valid-directive" ska komma med.
        $cspConfig = [
            'web' => [
                '1foo'           => ["'self'"],
                'foo1'           => ["'self'"],
                'valid-directive' => ["'self'"],
            ],
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $middleware->process($request, $handler);

        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        // Giltigt direktiv ska vara med
        $this->assertStringContainsString("valid-directive 'self'", $csp);

        // Ogiltiga direktivnamn ska inte förekomma i policyn
        $this->assertStringNotContainsString("1foo 'self'", $csp);
        $this->assertStringNotContainsString("foo1 'self'", $csp);
    }

    public function testNonArrayAndNonStringValuesAreHandledCorrectly(): void
    {
        $cspConfig = [
            'web' => [
                // Strängvärde (icke-array) ska hanteras som [$value] och komma med
                'string-value' => "'self'",
                // Icke-sträng, icke-tom ska filtreras bort av (!is_string($v) || $v === '')
                'non-string-value' => 123,
            ],
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $middleware->process($request, $handler);
        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        // Strängvärdet ska inkluderas
        $this->assertStringContainsString("string-value 'self'", $csp);

        // non-string-value (123) ska inte dyka upp alls
        $this->assertStringNotContainsString('non-string-value', $csp);
        $this->assertStringNotContainsString('123', $csp);
    }

    public function testDirectiveSkipsInvalidValueButKeepsFollowingValidValues(): void
    {
        // Första värdet är ogiltigt (icke-sträng), andra är giltigt.
        // Original: skippar 123 men behåller "'self'" → script-src 'self'.
        // Mutant (break i stället för continue): hoppar ur loopen vid 123 → inga tokens,
        // och därmed inget script-src-direktiv.
        $cspConfig = [
            'web' => [
                'script-src' => [
                    123,
                    "'self'",
                ],
            ],
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $middleware->process($request, $handler);
        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        // Vi förväntar oss att script-src fortfarande finns med giltigt värde
        $this->assertStringContainsString("script-src 'self'", $csp);
        // 123 ska inte dyka upp
        $this->assertStringNotContainsString("123", $csp);
    }

    public function testDirectiveWithOnlyEmptyValuesIsSkippedButDoesNotStopFollowingDirectives(): void
    {
        // Första direktivet har bara tomma/ogiltiga värden → ska hoppas över.
        // Andra direktivet har giltigt värde och ska fortfarande komma med.
        // Mutanten som byter continue→break skulle göra att andra direktivet aldrig behandlas.
        $cspConfig = [
            'web' => [
                'empty-dir' => ['', ''], // endast tomma strängar räknas som "inga värden"
                'default-src' => ["'self'"],
            ],
        ];

        $middleware = $this->makeMiddleware($cspConfig);
        $handler = $this->makeHandler();

        $request = new Request(
            uri: '/',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );

        $response = $middleware->process($request, $handler);
        $headers = $response->headers();

        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $csp = $headers['Content-Security-Policy'];

        // empty-dir ska inte förekomma alls
        $this->assertStringNotContainsString('empty-dir', $csp);

        // default-src ska fortfarande finnas – här märks skillnaden mot break-mutanten
        $this->assertStringContainsString("default-src 'self'", $csp);
    }
}
