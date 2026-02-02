<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\Response;
use ReflectionClass;
use ReflectionMethod;

final class ApiControllerTest extends TestCase
{
    public function testValidateApiTokenReturnsErrorWhenTokenMissing(): void
    {
        $request = new Request(
            uri: '/api/v1/resource',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: [
                // Ingen Authorization-header
            ]
        );

        $response = new Response();
        $controller = new TestApiController();

        // Sätt protected $request och ev. $response via Reflection
        $refClass = new ReflectionClass($controller);

        if ($refClass->hasProperty('request')) {
            $propRequest = $refClass->getProperty('request');
            $propRequest->setAccessible(true);
            $propRequest->setValue($controller, $request);
        }

        if ($refClass->hasProperty('response')) {
            $propResponse = $refClass->getProperty('response');
            $propResponse->setAccessible(true);
            $propResponse->setValue($controller, $response);
        }

        // Anropa private validateApiToken() via Reflection
        $method = $refClass->getMethod('validateApiToken');
        $method->setAccessible(true);
        $method->invoke($controller);

        $this->assertSame(401, $controller->lastStatus);
        $this->assertIsArray($controller->lastErrors);

        $this->assertArrayHasKey('API-token', $controller->lastErrors);
        $this->assertSame(
            ['Token saknas eller är ogiltig.'],
            $controller->lastErrors['API-token']
        );
    }

    /**
     * Vi kör i separat process för att kunna class_alias:a App\Models\Token innan den laddas.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsTokenValidReturnsFalseWhenEnvTokenIsSetButProvidedTokenDoesNotMatch(): void
    {
        self::bootFakeToken();

        putenv('API_TOKEN=expected-token');

        FakeToken::$firstResult = null; // DB hittar inget

        $controller = new TestApiController();

        $ref = new ReflectionMethod($controller, 'isTokenValid');
        $ref->setAccessible(true);

        $isValid = $ref->invoke($controller, 'wrong-token');

        self::assertFalse($isValid, 'Fel token får inte valideras bara för att API_TOKEN råkar vara satt.');
    }

    /**
     * Dödar CastString-mutanten genom att kräva att null hanteras utan TypeError.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsTokenValidReturnsFalseWhenDbTokenHasNullExpiresAt(): void
    {
        self::bootFakeToken();

        putenv('API_TOKEN'); // inget env-token (false)

        FakeToken::$firstResult = (object) [
            'expires_at' => null,
        ];

        $controller = new TestApiController();

        $ref = new ReflectionMethod($controller, 'isTokenValid');
        $ref->setAccessible(true);

        $isValid = $ref->invoke($controller, 'any-token');

        self::assertFalse($isValid, 'Token med null expires_at ska betraktas som ogiltig (och inte krascha).');
    }

    /**
     * Dödar LogicalOr-mutanten (|| -> &&) genom att testa "icke-tom men utgången" token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsTokenValidReturnsFalseWhenDbTokenIsExpired(): void
    {
        self::bootFakeToken();

        putenv('API_TOKEN'); // inget env-token (false)

        FakeToken::$firstResult = (object) [
            'expires_at' => date('Y-m-d H:i:s', time() - 3600),
        ];

        $controller = new TestApiController();

        $ref = new ReflectionMethod($controller, 'isTokenValid');
        $ref->setAccessible(true);

        $isValid = $ref->invoke($controller, 'any-token');

        self::assertFalse($isValid, 'Utgången token ska vara ogiltig.');
    }

    /**
     * Dödar LessThan-mutanten (< -> <=) stabilt genom fryst tid.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsTokenValidAcceptsDbTokenThatExpiresExactlyNow(): void
    {
        self::bootFakeToken();

        putenv('API_TOKEN'); // inget env-token (false)

        $controller = new TestApiController();
        $controller->frozenNow = 1_700_000_000;

        FakeToken::$firstResult = (object) [
            'expires_at' => date('Y-m-d H:i:s', $controller->frozenNow),
        ];

        $ref = new ReflectionMethod($controller, 'isTokenValid');
        $ref->setAccessible(true);

        $isValid = $ref->invoke($controller, 'any-token');

        self::assertTrue(
            $isValid,
            'Token som går ut exakt nu ska anses giltig enligt < now()-regeln (mutanten <= ska faila här).'
        );
    }

    private static function bootFakeToken(): void
    {
        if (!class_exists(\App\Models\Token::class, false)) {
            class_alias(FakeToken::class, \App\Models\Token::class);
        }
    }
}

/**
 * Minimal fake för App\Models\Token som klarar den fluent chain som ApiController använder:
 * Token::query()->where(...)->first()
 * Token::query()->where(...)->delete()->execute()
 */
final class FakeToken
{
    public static mixed $firstResult = null;

    public static function query(): FakeTokenQuery
    {
        return new FakeTokenQuery();
    }
}

final class FakeTokenQuery
{
    public function where(string $column, string $op, mixed $value): self
    {
        return $this;
    }

    public function first(): mixed
    {
        return FakeToken::$firstResult;
    }

    public function delete(): self
    {
        return $this;
    }

    public function execute(): void
    {
        // no-op
    }
}
