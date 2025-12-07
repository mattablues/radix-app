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

    public function testMaxAgeZeroDoesNotAddHeader(): void
    {
        $config = new Config([
            'cors' => [
                'enabled' => true,
                'paths' => ['/api'],
                'allow_origins' => ['*'],
                'allow_methods' => ['GET', 'POST', 'OPTIONS'],
                'allow_headers' => ['Authorization'],
                'expose_headers' => [],
                'max_age' => 0,
                'allow_credentials' => false,
            ],
        ]);

        $listener = new CorsListener($config);

        $request = new Request(
            uri: '/api/test',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_ORIGIN' => 'https://example.com']
        );
        $response = new Response();

        $event = new ResponseEvent($request, $response);
        // Anropa den som en invokable listener
        $listener($event);

        $headers = $response->getHeaders();

        $this->assertArrayNotHasKey(
            'Access-Control-Max-Age',
            $headers,
            'max_age=0 ska inte sätta Access-Control-Max-Age.'
        );
    }

    public function testMaxAgePositiveAddsHeader(): void
    {
        $config = new Config([
            'cors' => [
                'enabled' => true,
                'paths' => ['/api'],
                'allow_origins' => ['*'],
                'allow_methods' => ['GET', 'POST', 'OPTIONS'],
                'allow_headers' => ['Authorization'],
                'expose_headers' => [],
                'max_age' => 600,
                'allow_credentials' => false,
            ],
        ]);

        $listener = new CorsListener($config);

        $request = new Request(
            uri: '/api/test',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_ORIGIN' => 'https://example.com']
        );
        $response = new Response();

        $event = new ResponseEvent($request, $response);
        $listener($event);

        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Access-Control-Max-Age', $headers);
        $this->assertSame('600', $headers['Access-Control-Max-Age']);
    }

    public function testPreflightIsCaseInsensitiveOnMethod(): void
    {
        $config = new Config([
            'cors' => [
                'enabled' => true,
                'paths' => ['/api'],
                'allow_origins' => ['*'],
                'allow_methods' => ['GET', 'POST', 'OPTIONS'],
                'allow_headers' => ['Authorization'],
                'expose_headers' => [],
                'max_age' => 0,
                'allow_credentials' => false,
            ],
        ]);

        $listener = new CorsListener($config);

        $request = new Request(
            uri: '/api/test',
            method: 'options', // gemener
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: ['HTTP_ORIGIN' => 'https://example.com']
        );
        $response = new Response();

        $event = new ResponseEvent($request, $response);
        $listener($event);

        $this->assertSame(
            204,
            $response->getStatusCode(),
            'Preflight OPTIONS ska ge 204 även om metodnamnet är i gemener.'
        );
    }

    public function testExposeHeadersMergesExistingAndConfigTrimmedAndDeduplicated(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            'allow_methods' => ['GET'],
            'allow_headers' => ['Authorization'],
            'expose_headers' => ['X-Trace-Id', 'X-New-Header'],
            'max_age' => 0,
            'allow_credentials' => false,
        ]);

        // Gör ett event där responsen redan har en Expose-header med mellanslag
        $event = $this->makeEvent('/api/resource', 'GET');
        $response = $event->response();

        // Befintlig header med mellanslag och ett värde som också finns i config
        $response->setHeader('Access-Control-Expose-Headers', 'X-Request-Id, X-Trace-Id');

        // Kör lyssnaren
        $listener($event);

        $headers = $response->getHeaders();
        $expose = $headers['Access-Control-Expose-Headers'] ?? '';

        // Förväntat beteende i originalkoden:
        // - "X-Request-Id" bevaras
        // - "X-Trace-Id" trimmas och dubbletten från config tas bort
        // - "X-New-Header" läggs till
        // - inga extra mellanslag
        $this->assertSame(
            'X-Request-Id,X-Trace-Id,X-New-Header',
            $expose,
            'Expose headers ska vara trimmade, merged och deduplicerade.'
        );
    }

    public function testStringZeroForAllowCredentialsBehavesAsFalse(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['https://allowed.com'],
            'allow_credentials' => '0', // sträng, ska ändå tolkas som false
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => 'https://allowed.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Origin ska spegla tillåten origin...
        $this->assertSame('https://allowed.com', $headers['Access-Control-Allow-Origin'] ?? null);
        // ...men inga credentials ska tillåtas när allow_credentials är "0"
        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
    }

    public function testCredentialsModeDoesNotUseEmptyOriginHeader(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            'allow_credentials' => true,
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => '', // tom sträng
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: originToUse förblir '*'
        $this->assertSame('*', $headers['Access-Control-Allow-Origin'] ?? null);
        // (vi bryr oss inte om Allow-Credentials här)
    }

    public function testNonCredentialsModeWithEmptyOriginStillSetsWildcard(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            // Ingen '*' i allow_origins – vi testar nu beteendet när origin saknas/tom
            'allow_origins' => ['https://allowed.com'],
            'allow_credentials' => false,
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => '', // tom Origin
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: ingen match i elseif → ingen return → header sätts till '*'
        $this->assertSame('*', $headers['Access-Control-Allow-Origin'] ?? null);
    }

    public function testDefaultMaxAgeIs600WhenNotConfigured(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            'allow_methods' => ['GET'],
            'allow_headers' => ['Authorization'],
            // OBS: ingen 'max_age' här → vi testar default
            // 'max_age' => 600,
            'expose_headers' => [],
            'allow_credentials' => false,
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertArrayHasKey('Access-Control-Max-Age', $headers);
        $this->assertSame('600', $headers['Access-Control-Max-Age']);
    }

    public function testEmptyAllowMethodsFallsBackToDefaultList(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            'allow_methods' => [], // tom lista → ska ge default
        ]);

        $event = $this->makeEvent('/api/resource', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertSame(
            'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            $headers['Access-Control-Allow-Methods'] ?? null
        );
    }

    public function testDefaultAllowHeadersIncludesAuthorizationWhenNotConfigured(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            // OBS: ingen 'allow_headers' här → vi testar defaultlistan
        ]);

        $event = $this->makeEvent('/api/resource', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertSame(
            'Authorization,Content-Type,X-Requested-With',
            $headers['Access-Control-Allow-Headers'] ?? null
        );
    }

    public function testEmptyAllowHeadersFallsBackToDefaultList(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            'allow_headers' => [], // tom lista → ska ge default
        ]);

        $event = $this->makeEvent('/api/resource', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertSame(
            'Authorization,Content-Type,X-Requested-With',
            $headers['Access-Control-Allow-Headers'] ?? null
        );
    }

    public function testEmptyAllowOriginsFallsBackToWildcard(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => [], // tom → ska bli ['*']
        ]);

        $event = $this->makeEvent('/api/resource', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertSame(
            '*',
            $headers['Access-Control-Allow-Origin'] ?? null,
            'Tom allow_origins ska falla tillbaka till wildcard-origin "*".'
        );
    }

    public function testDefaultAllowMethodsIncludesGetWhenNotConfigured(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
            // ingen 'allow_methods' här → vi testar default-listan
        ]);

        $event = $this->makeEvent('/api/resource', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        $this->assertSame(
            'GET,POST,PUT,PATCH,DELETE,OPTIONS',
            $headers['Access-Control-Allow-Methods'] ?? null,
            'Default allow_methods ska innehålla GET när det inte är konfigurerat.'
        );
    }

    public function testEmptyPathPrefixDoesNotMatchAnyUri(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => [''], // tom path-prefix ska inte matcha något
            'allow_origins' => ['*'],
        ]);

        $event = $this->makeEvent('/web/home', 'GET');
        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: inget path matchar → ingen CORS-header
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
    }

    public function testNonArrayAllowOriginsFallsBackToWildcard(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            'paths' => ['/api/'],
            'allow_origins' => 'https://not-an-array.example', // fel typ
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => 'https://any-origin.example',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: feltypad allow_origins -> fallback till ['*']
        $this->assertSame(
            '*',
            $headers['Access-Control-Allow-Origin'] ?? null,
            'Fel typ för allow_origins ska falla tillbaka till wildcard-origin "*".'
        );
    }

    public function testListenerIsDisabledByDefaultWhenEnabledNotConfigured(): void
    {
        $listener = $this->makeListener([
            // OBS: ingen 'enabled'-nyckel här
            'paths' => ['/api/'],
            'allow_origins' => ['*'],
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: enabled defaultar till false => inga CORS-headers alls
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayNotHasKey('Access-Control-Allow-Headers', $headers);
    }

    public function testNonArrayPathsAreIgnoredAndCorsStillApplies(): void
    {
        $listener = $this->makeListener([
            'enabled' => true,
            // Feltypad paths: ska ignoreras och inte hindra CORS
            'paths' => 'Z',
            'allow_origins' => ['*'],
        ]);

        $event = $this->makeEvent('/api/resource', 'GET', [
            'HTTP_ORIGIN' => 'https://example.com',
        ]);

        $listener($event);

        $response = $event->response();
        $headers = $response->getHeaders();

        // Original: paths ignoreras => global CORS => '*'
        $this->assertSame('*', $headers['Access-Control-Allow-Origin'] ?? null);
    }
}
