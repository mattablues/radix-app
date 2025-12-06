<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\Response;
use ReflectionClass;

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
}
