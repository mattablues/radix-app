<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;

final class RequestFilterFieldsTest extends TestCase
{
    public function testFilterFieldsRemovesDefaultExcludedKeys(): void
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
            uri: '/submit',
            method: 'POST',
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server
        );

        $data = [
            'name' => 'Alice',
            'email' => 'a@example.com',
            'csrf_token' => 'XYZ',
            'password_confirmation' => 'secret',
            'honeypot' => '',
            'message' => 'Hej',
        ];

        $filtered = $request->filterFields($data);

        // Dessa nycklar ska vara borttagna
        $this->assertArrayNotHasKey('csrf_token', $filtered);
        $this->assertArrayNotHasKey('password_confirmation', $filtered);
        $this->assertArrayNotHasKey('honeypot', $filtered);

        // Övriga nycklar ska vara kvar oförändrade
        $this->assertSame('Alice', $filtered['name']);
        $this->assertSame('a@example.com', $filtered['email']);
        $this->assertSame('Hej', $filtered['message']);
    }
}
