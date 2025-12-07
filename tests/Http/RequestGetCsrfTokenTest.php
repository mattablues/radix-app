<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;

final class RequestGetCsrfTokenTest extends TestCase
{
    public function testGetCsrfTokenReturnsNullWhenTokenIsNotString(): void
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];
        /** @var array<string,mixed> $server */
        $server = [];

        // 1) csrf_token som array
        $post = ['csrf_token' => ['not', 'a', 'string']];

        $request = new Request(
            uri: '/submit',
            method: 'POST',
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $this->assertNull($request->getCsrfToken(), 'När csrf_token inte är en sträng ska getCsrfToken() returnera null.');

        // 2) csrf_token saknas helt
        $request2 = new Request(
            uri: '/submit',
            method: 'POST',
            get: $get,
            post: [],
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $this->assertNull($request2->getCsrfToken());

        // 3) csrf_token är int
        $request3 = new Request(
            uri: '/submit',
            method: 'POST',
            get: $get,
            post: ['csrf_token' => 123],
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $this->assertNull($request3->getCsrfToken());
    }

    public function testGetCsrfTokenReturnsStringWhenValid(): void
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];
        /** @var array<string,mixed> $server */
        $server = [];
        /** @var array<string,mixed> $post */
        $post = ['csrf_token' => 'VALID123'];

        $request = new Request(
            uri: '/submit',
            method: 'POST',
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $this->assertSame('VALID123', $request->getCsrfToken());
    }
}
