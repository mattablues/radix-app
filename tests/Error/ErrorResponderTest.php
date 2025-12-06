<?php

declare(strict_types=1);

namespace Radix\Tests\Error;

use PHPUnit\Framework\TestCase;
use Radix\Error\ErrorResponder;
use Radix\Http\Request;

final class ErrorResponderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Samma mönster som i CsrfMiddlewareTest: se till att ROOT_PATH är satt
        $projectRoot = dirname(__DIR__, 2);
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $projectRoot);
        }
    }

    /**
     * Hjälpare för att skapa Request med korrekt signatur.
     *
     * @param array<string,mixed> $server
     */
    private function makeRequest(string $uri, string $method = 'GET', array $server = []): Request
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $post */
        $post = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];

        return new Request(
            uri: $uri,
            method: $method,
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server,
        );
    }

    /**
     * API‑anrop med korrekt prefix ska ge JSON och Content‑Type application/json.
     *
     * Dödar bl.a:
     * - [M] Identical (=== -> !==)
     * - [M] IfNegation på $isApi
     */
    public function testRespondForApiRequestReturnsJsonResponse(): void
    {
        $request   = $this->makeRequest('/api/v1/users', 'GET');
        $status    = 400;
        $message   = 'Bad request';
        $jsonExtra = ['foo' => 'bar'];

        $response = ErrorResponder::respond($request, $status, $message, $jsonExtra);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'application/json; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body    = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertSame($message, $decoded['error'] ?? null);
        $this->assertSame($status, $decoded['status'] ?? null);
        $this->assertSame('bar', $decoded['foo'] ?? null);
    }

    /**
     * En URL som INTE börjar med /api/... men innehåller /api/... senare
     * ska behandlas som web (HTML), inte API.
     *
     * Dödar:
     * - [M] PregMatchRemoveCaret (borttagen ^ i regexen)
     */
    public function testRespondDoesNotTreatNonRootedApiPathAsApi(): void
    {
        $request = $this->makeRequest('/foo/api/v1/users', 'GET');
        $status  = 404;
        $message = 'Not found';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null,
            'En icke-rootad /api-path ska behandlas som HTML/web.'
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);
        $this->assertStringContainsString((string) $status, $body);
    }

    /**
     * När en specifik vy finns (t.ex. 404.php) ska den användas i första hand.
     *
     * Dödar:
     * - Concat/ConcatOperandRemoval‑mutanter för $errorFile
     * - IfNegation på is_file($errorFile)
     * - Ternary‑mutanten genom att säkerställa att vi får exakt filens innehåll
     * - MethodCallRemoval på setBody i web‑grenen
     *
     * OBS: förutsätter att ROOT_PATH . "/views/errors/404.php" finns.
     */
    public function testRespondWebUsesSpecificErrorViewWhenItExists(): void
    {
        $request = $this->makeRequest('/some/normal/page', 'GET');
        $status  = 404;
        $message = 'Page not found';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);

        // Förväntar oss att den specifika 404-vyn används, inte fallback eller default-h1.
        $this->assertStringContainsString('404', $body);
        $this->assertStringNotContainsString(
            "<h1>{$status} | {$message}</h1>",
            $body,
            'Body ska INTE vara default-h1 när en vyfil finns.'
        );
    }

    /**
     * När specifik vy inte finns ska fallback‑filen (t.ex. 500.php) användas.
     *
     * Dödar:
     * - Concat/ConcatOperandRemoval för $fallback
     * - IfNegation på is_file‑villkoret
     * - Ternary‑mutanten (vi säkerställer att fallback‑view används, inte default-h1)
     *
     * Välj en statuskod där du inte har en vyfil (t.ex. 599 eller 418),
     * men där ROOT_PATH . "/views/errors/500.php" finns.
     */
    public function testRespondWebFallsBackTo500ViewWhenSpecificViewDoesNotExist(): void
    {
        $request = $this->makeRequest('/another/normal/page', 'GET');
        $status  = 599;
        $message = 'Unexpected error';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);

        // Antag att 500-vyn har något unikt (t.ex. "500" i HTML:en)
        $this->assertStringContainsString('500', $body);

        $this->assertStringNotContainsString(
            "<h1>{$status} | {$message}</h1>",
            $body,
            'Fallback-svaret ska komma från 500.php, inte från default-h1‑template.'
        );
    }
}
