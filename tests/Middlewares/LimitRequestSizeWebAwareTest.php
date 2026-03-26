<?php

declare(strict_types=1);

namespace Radix\Tests\Middlewares;

use App\Middlewares\LimitRequestSizeWebAware;
use PHPUnit\Framework\TestCase;
use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Session\SessionInterface;
use ReflectionClass;

final class LimitRequestSizeWebAwareTest extends TestCase
{
    private const int MB = 1048576;

    /**
     * @param array<string, mixed> $server
     */
    private function runMiddleware(array $server, ?int $maxBytes = null, ?SessionInterface $session = null): Response
    {
        $middleware = $maxBytes === null
            ? new LimitRequestSizeWebAware()
            : new LimitRequestSizeWebAware($maxBytes);

        $handler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                $res = new Response();
                $res->setStatusCode(200);
                $res->setHeader('Content-Type', 'text/plain; charset=utf-8');
                $res->setBody('OK');
                return $res;
            }
        };

        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $server
        );

        if ($session !== null) {
            $request->setSession($session);
        }

        return $middleware->process($request, $handler);
    }

    public function testDefaultLimitIsReadFromEnvInMegabytes(): void
    {
        $old = getenv('WEB_MAX_REQUEST_MB');
        putenv('WEB_MAX_REQUEST_MB=6');

        try {
            $under = [
                'CONTENT_LENGTH' => (string) (int) (5.5 * self::MB),
            ];
            $over = [
                'CONTENT_LENGTH' => (string) (int) (6.5 * self::MB),
                'HTTP_ACCEPT' => 'application/json',
            ];

            $responseUnder = $this->runMiddleware($under); // default via env
            self::assertSame(200, $responseUnder->getStatusCode());

            $responseOver = $this->runMiddleware($over); // default via env
            self::assertSame(413, $responseOver->getStatusCode());
        } finally {
            if ($old === false) {
                putenv('WEB_MAX_REQUEST_MB');
            } else {
                putenv('WEB_MAX_REQUEST_MB=' . $old);
            }
        }
    }

    public function testRequestExactlyAtLimitIsAllowed(): void
    {
        $limit = 2 * self::MB;

        $server = [
            'CONTENT_LENGTH' => (string) $limit,
        ];

        $response = $this->runMiddleware($server, $limit);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testContentLengthPreferredOverHttpContentLength(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (1 * self::MB),
            'HTTP_CONTENT_LENGTH' => (string) (5 * self::MB),
            'HTTP_ACCEPT' => 'application/json',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testNonStringContentLengthIsIgnoredAndPassesThrough(): void
    {
        $server = [
            'CONTENT_LENGTH' => [1], // ska ignoreras
        ];

        // Väljer maxBytes=0 för att en felaktig cast/fortsättning ska ge 413 och därmed döda mutanten
        $response = $this->runMiddleware($server, 0);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testInvalidContentLengthIsIgnoredAndPassesThrough(): void
    {
        $server = [
            'CONTENT_LENGTH' => '12abc', // inte digit => ska ignoreras
        ];

        $response = $this->runMiddleware($server, 0);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testOverLimitWithJsonAcceptReturnsJson413WithBody(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_ACCEPT' => 'application/json',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(413, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);

        $body = $response->getBody();
        self::assertIsString($body);
        self::assertNotSame('', $body);

        $data = json_decode($body, true);
        self::assertIsArray($data);

        self::assertArrayHasKey('error', $data);
        self::assertSame('Payload Too Large', $data['error']);

        self::assertArrayHasKey('message', $data);
        self::assertArrayHasKey('max_bytes', $data);
        self::assertSame(2 * self::MB, $data['max_bytes']);
    }

    public function testOverLimitWithXmlHttpRequestHeaderReturnsJson413(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(413, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
    }

    public function testOverLimitWithoutJsonAcceptRedirectsBackAndSetsFlash(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('setFlashMessage');

        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_REFERER' => '/post/create',
            'HTTP_ACCEPT' => 'text/html',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB, $session);

        self::assertInstanceOf(RedirectResponse::class, $response);

        self::assertSame('/post/create', $this->getRedirectLocation($response));
    }

    public function testUnderLimitPassesThrough(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (1 * self::MB),
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testNonStringZeroContentLengthDoesNotTriggerLimit(): void
    {
        $server = [
            'CONTENT_LENGTH' => 0, // int, inte string
            'HTTP_ACCEPT' => 'application/json',
        ];

        // maxBytes=-1: om mutanten råkar tolka int 0 som giltig length => 0 > -1 => 413 (fel)
        $response = $this->runMiddleware($server, -1);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testHugeNumericStringIsCastedToIntAndShouldNotBlockAtIntMax(): void
    {
        $max = PHP_INT_MAX;

        // En siffra som garanterat är större än PHP_INT_MAX, men som string.
        // Original: (int)$value => PHP_INT_MAX => length > max blir false => 200
        // Mutant (utan cast): kan jämföra som större än max => 413
        $server = [
            'CONTENT_LENGTH' => (string) $max . '0',
            'HTTP_ACCEPT' => 'application/json',
        ];

        $response = $this->runMiddleware($server, $max);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testJsonEncodingDoesNotEscapeUnicodeCharacters(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_ACCEPT' => 'application/json',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(413, $response->getStatusCode());

        $body = $response->getBody();
        self::assertIsString($body);

        // "är" ska finnas som riktig UTF-8, inte som \u00e4
        self::assertStringContainsString('är', $body);
        self::assertStringNotContainsString('\\u00e4', $body);
    }

    public function testNonStringRefererFallsBackToRoot(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('setFlashMessage');

        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_REFERER' => ['not-a-string'],
            'HTTP_ACCEPT' => 'text/html',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB, $session);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/', $this->getRedirectLocation($response));
    }

    public function testAcceptHeaderIsCaseInsensitiveForJsonNegotiation(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
            'HTTP_ACCEPT' => 'Application/JSON',
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(413, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
    }

    public function testStringableObjectContentLengthIsIgnoredAndPassesThrough(): void
    {
        $stringableDigits = new class {
            public function __toString(): string
            {
                return '10';
            }
        };

        $server = [
            'CONTENT_LENGTH' => $stringableDigits,
            'HTTP_ACCEPT' => 'application/json',
        ];

        // Viktigt: använd maxBytes=1 för att skippa env-branch.
        $response = $this->runMiddleware($server, 1);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testIntegerContentLengthIsIgnoredEvenIfCtypeDigitWouldMatchAsciiDigit(): void
    {
        $server = [
            'CONTENT_LENGTH' => 48, // ASCII '0'
            'HTTP_ACCEPT' => 'application/json',
        ];

        // Viktigt: använd maxBytes=1 för att skippa env-branch.
        $response = $this->runMiddleware($server, 1);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testConstructorDefaultMaxBytesIsExactlyZero(): void
    {
        $mw = new LimitRequestSizeWebAware();

        $ref = new ReflectionClass($mw);
        $prop = $ref->getProperty('maxBytes');
        $prop->setAccessible(true);

        self::assertSame(0, $prop->getValue($mw));
    }

    public function testEnvUnsetFallsBackToDefaultSixMb(): void
    {
        $old = getenv('WEB_MAX_REQUEST_MB');
        putenv('WEB_MAX_REQUEST_MB'); // unset

        try {
            // 5.5 MB ska tillåtas om default = 6
            $under = [
                'CONTENT_LENGTH' => (string) (int) (5.5 * self::MB),
            ];
            $r1 = $this->runMiddleware($under);
            self::assertSame(200, $r1->getStatusCode());

            // 6.5 MB ska blockas om default = 6 (men INTE om mutanten råkar vara 7)
            $over = [
                'CONTENT_LENGTH' => (string) (int) (6.5 * self::MB),
                'HTTP_ACCEPT' => 'application/json',
            ];
            $r2 = $this->runMiddleware($over);
            self::assertSame(413, $r2->getStatusCode());
        } finally {
            if ($old === false) {
                putenv('WEB_MAX_REQUEST_MB');
            } else {
                putenv('WEB_MAX_REQUEST_MB=' . $old);
            }
        }
    }

    public function testEnvValueIsRespectedNotForcedToSix(): void
    {
        $old = getenv('WEB_MAX_REQUEST_MB');
        putenv('WEB_MAX_REQUEST_MB=9');

        try {
            // Med 9 MB ska 8.5 MB gå igenom.
            // Mutanten "getenv(...) ? 6 : getenv(...)" skulle annars tvinga 6 och blocka.
            $server = [
                'CONTENT_LENGTH' => (string) (int) (8.5 * self::MB),
                'HTTP_ACCEPT' => 'application/json',
            ];

            $res = $this->runMiddleware($server);

            self::assertSame(200, $res->getStatusCode());
            self::assertSame('OK', $res->getBody());
        } finally {
            if ($old === false) {
                putenv('WEB_MAX_REQUEST_MB');
            } else {
                putenv('WEB_MAX_REQUEST_MB=' . $old);
            }
        }
    }

    public function testEnvZeroIsClampedUpToOneMegabyte(): void
    {
        $old = getenv('WEB_MAX_REQUEST_MB');
        putenv('WEB_MAX_REQUEST_MB=0');

        try {
            $server = [
                'CONTENT_LENGTH' => '1', // 1 byte
                'HTTP_ACCEPT' => 'application/json',
            ];

            $res = $this->runMiddleware($server); // env-branch

            self::assertSame(200, $res->getStatusCode());
            self::assertSame('OK', $res->getBody());
        } finally {
            if ($old === false) {
                putenv('WEB_MAX_REQUEST_MB');
            } else {
                putenv('WEB_MAX_REQUEST_MB=' . $old);
            }
        }
    }

    public function testEnvOneIsNotClampedUpToTwoMegabytes(): void
    {
        $old = getenv('WEB_MAX_REQUEST_MB');
        putenv('WEB_MAX_REQUEST_MB=1');

        try {
            $server = [
                'CONTENT_LENGTH' => (string) (int) (1.5 * self::MB),
                'HTTP_ACCEPT' => 'application/json',
            ];

            $res = $this->runMiddleware($server); // env-branch

            self::assertSame(413, $res->getStatusCode());
        } finally {
            if ($old === false) {
                putenv('WEB_MAX_REQUEST_MB');
            } else {
                putenv('WEB_MAX_REQUEST_MB=' . $old);
            }
        }
    }

    private function getRedirectLocation(RedirectResponse $response): string
    {
        $ref = new ReflectionClass($response);
        $property = $ref->getProperty('location');
        $property->setAccessible(true);

        $location = $property->getValue($response);

        $this->assertIsString($location);

        return $location;
    }
}
