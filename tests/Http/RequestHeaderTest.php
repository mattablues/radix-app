<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;

final class RequestHeaderTest extends TestCase
{
    public function testStandardHeaderLookupNormalizesCaseWithUcFirstAndStrToLower(): void
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $post */
        $post = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];
        /** @var array<string,mixed> $server */
        $server = [
            'X_custom_header' => 'OK',
        ];

        $request = new Request(
            uri: '/test',
            method: 'GET',
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $value = $request->header('X-CUSTOM-HEADER');
        $this->assertSame('OK', $value);
    }

    public function testNonAuthorizationHeaderDoesNotUseAuthorizationFallback(): void
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $post */
        $post = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];
        /** @var array<string,mixed> $server */
        $server = [];

        $request = new Request(
            uri: '/test',
            method: 'GET',
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $value = $request->header('X-Custom', 'fallback');

        $this->assertSame('fallback', $value);
    }
}
