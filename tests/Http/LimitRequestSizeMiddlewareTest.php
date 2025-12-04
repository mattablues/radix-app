<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\Middlewares\LimitRequestSize;

final class LimitRequestSizeMiddlewareTest extends TestCase
{
    private const int MB = 1048576;

    /**
     * Hjälpfunktion för att köra middleware med given server-array och ev. maxBytes.
     *
     * @param array<string, mixed> $server
     */
    private function runMiddleware(array $server, ?int $maxBytes = null): Response
    {
        $middleware = $maxBytes === null
            ? new LimitRequestSize()
            : new LimitRequestSize($maxBytes);

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

        return $middleware->process($request, $handler);
    }

    public function testRequestOverLimitReturns413(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(413, $response->getStatusCode());
    }

    public function testRequestUnderLimitPassesThrough(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (1 * self::MB),
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testRequestExactlyAtLimitIsAllowed(): void
    {
        $limit = 2 * self::MB;
        $server = [
            'CONTENT_LENGTH' => (string) $limit,
        ];

        $response = $this->runMiddleware($server, $limit);

        // size == max → ska vara tillåten (isTooLarge använder >, inte >=)
        self::assertSame(200, $response->getStatusCode());
    }

    public function testDefaultLimitIsTwoMegabytes(): void
    {
        // Default ska vara 2 MB. 1.5 MB ska gå igenom, 2.5 MB ska blockeras.

        $under = [
            'CONTENT_LENGTH' => (string) (int) (1.5 * self::MB),
        ];
        $over = [
            'CONTENT_LENGTH' => (string) (int) (2.5 * self::MB),
        ];

        $responseUnder = $this->runMiddleware($under); // använder default
        $responseOver  = $this->runMiddleware($over);  // använder default

        self::assertSame(200, $responseUnder->getStatusCode(), '1.5 MB ska tillåtas av default-limit');
        self::assertSame(413, $responseOver->getStatusCode(), '2.5 MB ska nekas av default-limit');
    }

    public function testNoContentLengthHeaderDoesNotThrowAndPassesThrough(): void
    {
        $server = []; // ingen CONTENT_LENGTH/HTTP_CONTENT_LENGTH

        $response = $this->runMiddleware($server, 1); // extremt låg limit spelar ingen roll här

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getBody());
    }

    public function testContentLengthPreferredOverHttpContentLength(): void
    {
        // CONTENT_LENGTH liten, HTTP_CONTENT_LENGTH jättestor.
        // Rätt beteende: CONTENT_LENGTH används → request tillåts (200).
        $server = [
            'CONTENT_LENGTH' => (string) (1 * self::MB),
            'HTTP_CONTENT_LENGTH' => (string) (5 * self::MB),
        ];

        $response = $this->runMiddleware($server, 2 * self::MB);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testTooLargeResponseHasJsonContentTypeAndErrorKey(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
        ];

        $limit = 2 * self::MB;

        $response = $this->runMiddleware($server, $limit);

        self::assertSame(413, $response->getStatusCode());

        $headers = $response->getHeaders();
        self::assertArrayHasKey('Content-Type', $headers);
        self::assertSame('application/json; charset=utf-8', $headers['Content-Type']);

        $body = $response->getBody();
        $data = json_decode($body, true);

        self::assertIsArray($data);
        self::assertArrayHasKey('error', $data);
        self::assertSame('Payload Too Large', $data['error']);
        self::assertArrayHasKey('message', $data);
        self::assertArrayHasKey('max_bytes', $data);
        self::assertSame($limit, $data['max_bytes']);
        self::assertSame('Max 2 MB tillåts.', $data['message']);
    }

    public function testBytesToMegabytesRoundingIsReflectedInMessage(): void
    {
        // maxBytes strax under 2 MB ska fortfarande ge text "Max 2 MB tillåts."
        $almostTwoMb = (2 * self::MB) - 123;
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
        ];

        $response = $this->runMiddleware($server, $almostTwoMb);

        self::assertSame(413, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        self::assertIsArray($data);
        self::assertSame('Max 2 MB tillåts.', $data['message']);
    }

    public function testJsonEncodingDoesNotEscapeSlashes(): void
    {
        $server = [
            'CONTENT_LENGTH' => (string) (3 * self::MB),
        ];

        $limit = 2 * self::MB;

        $response = $this->runMiddleware($server, $limit);

        self::assertSame(413, $response->getStatusCode());

        $body = $response->getBody();

        // Vår doc-url ska finnas rak utan escapade slashes
        self::assertStringContainsString('https://httpstatuses.com/413', $body);
        self::assertStringNotContainsString('https:\/\/httpstatuses.com\/413', $body);
    }

    public function testBytesToMegabytesBoundariesViaMessage(): void
    {
        // 1 byte → 1 MB i meddelandet (ceil-uppåt och clamp till minst 1)
        $limitTiny = 1;
        $server = ['CONTENT_LENGTH' => (string) (2 * self::MB)];
        $responseTiny = $this->runMiddleware($server, $limitTiny);
        $dataTiny = json_decode($responseTiny->getBody(), true);
        self::assertIsArray($dataTiny);
        self::assertSame('Max 1 MB tillåts.', $dataTiny['message']);

        // Precis 1 MB
        $limitOneMb = self::MB;
        $responseOne = $this->runMiddleware($server, $limitOneMb);
        $dataOne = json_decode($responseOne->getBody(), true);
        self::assertIsArray($dataOne);
        self::assertSame('Max 1 MB tillåts.', $dataOne['message']);

        // 1 MB + 1 byte → 2 MB i meddelandet
        $limitOnePlus = self::MB + 1;
        $responseOnePlus = $this->runMiddleware($server, $limitOnePlus);
        $dataOnePlus = json_decode($responseOnePlus->getBody(), true);
        self::assertIsArray($dataOnePlus);
        self::assertSame('Max 2 MB tillåts.', $dataOnePlus['message']);
    }
}