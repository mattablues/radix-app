<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Config\Config;
use Radix\Http\Event\ResponseEvent;
use Radix\Http\EventListeners\CorsListener;
use Radix\Http\Request;
use Radix\Http\Response;

/**
 * Enhetstester för CorsListener, med explicit Config-injektion (ingen global app()).
 */
final class CorsListenerTest extends TestCase
{
    /**
     * Hjälpfunktion för att skapa en CorsListener med given CORS-konfig.
     *
     * @param array<string, mixed> $corsConfig
     */
    private function makeListener(array $corsConfig): CorsListener
    {
        $config = new Config([
            'cors' => $corsConfig,
        ]);

        return new CorsListener($config);
    }

    /**
     * @param array<string, mixed> $server
     */
    private function makeEvent(string $uri, string $method, array $server = []): ResponseEvent
    {
        /** @var array<string, mixed> $get */
        $get = [];
        /** @var array<string, mixed> $post */
        $post = [];
        /** @var array<string, mixed> $files */
        $files = [];
        /** @var array<string, mixed> $cookie */
        $cookie = [];
        /** @var array<string, mixed> $serverArray */
        $serverArray = $server;

        $request = new Request($uri, $method, $get, $post, $files, $cookie, $serverArray);
        $response = new Response();

        return new ResponseEvent($request, $response);
    }

    public function testCorsAppliedToMatchingApiPathWithWildcardOrigin(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['*'],
            'allow_methods' => ['GET', 'POST'],
            'allow_headers' => ['Authorization', 'Content-Type'],
            'expose_headers' => ['X-Request-Id'],
            'max_age' => 600,
            'allow_credentials' => false,
        ]);

        $event = $this->makeEvent('/api/v1/users', 'GET');

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        self::assertSame('*', $headers['Access-Control-Allow-Origin'] ?? null);
        self::assertSame('GET,POST', $headers['Access-Control-Allow-Methods'] ?? null);
        self::assertSame('Authorization,Content-Type', $headers['Access-Control-Allow-Headers'] ?? null);
        self::assertSame('X-Request-Id', $headers['Access-Control-Expose-Headers'] ?? null);
        self::assertSame('600', $headers['Access-Control-Max-Age'] ?? null);
        self::assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
    }

    public function testCorsNotAppliedToNonMatchingPath(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['*'],
        ]);

        $event = $this->makeEvent('/web/home', 'GET');

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        self::assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
    }

    public function testSpecificOriginIsReflectedWhenAllowed(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['https://example.com'],
            'allow_methods' => ['GET'],
            'allow_headers' => ['Authorization'],
            'expose_headers' => [],
            'max_age' => 0,
            'allow_credentials' => false,
        ]);

        $event = $this->makeEvent('/api/v1/data', 'GET', [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        self::assertSame('https://example.com', $headers['Access-Control-Allow-Origin'] ?? null);
    }

    public function testOriginNotInAllowListDoesNotGetCorsHeaders(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['https://allowed.com'],
        ]);

        $event = $this->makeEvent('/api/v1/data', 'GET', [
            'HTTP_ORIGIN' => 'https://evil.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
    }

    public function testCredentialsModeUsesRequestOrigin(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['https://allowed.com'],
            'allow_credentials' => true,
        ]);

        $event = $this->makeEvent('/api/v1/data', 'GET', [
            'HTTP_ORIGIN' => 'https://allowed.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        self::assertSame('https://allowed.com', $headers['Access-Control-Allow-Origin'] ?? null);
        self::assertSame('true', $headers['Access-Control-Allow-Credentials'] ?? null);
    }

    public function testOptionsPreflightReturns204(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/v1/'],
            'allow_origins' => ['*'],
        ]);

        $event = $this->makeEvent('/api/v1/data', 'OPTIONS');

        $listener($event);

        $response = $event->response();

        self::assertSame(204, $response->getStatusCode());
        $headers = $response->getHeaders();
        self::assertSame('*', $headers['Access-Control-Allow-Origin'] ?? null);
    }
}
