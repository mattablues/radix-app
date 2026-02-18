<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use App\Middlewares\IpAllowlist;
use PHPUnit\Framework\TestCase;
use Radix\Config\Dotenv;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;

final class IpAllowlistDotenvIntegrationTest extends TestCase
{
    private ?string $tmpEnvPath = null;

    protected function tearDown(): void
    {
        if (is_string($this->tmpEnvPath) && file_exists($this->tmpEnvPath)) {
            @unlink($this->tmpEnvPath);
        }
        $this->tmpEnvPath = null;

        putenv('HEALTH_IP_ALLOWLIST');
        putenv('APP_ENV');
        putenv('TRUSTED_PROXY');

        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    private function writeTempEnv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'radix-dotenv-');
        $this->assertIsString($path, 'Kunde inte skapa temporär .env-fil.');

        $ok = file_put_contents($path, $contents);
        if ($ok === false) {
            $this->fail('Kunde inte skriva temporär .env-fil.');
        }

        $this->tmpEnvPath = $path;
        return $path;
    }

    private function runIpAllowlist(string $remoteAddr, ?string $xForwardedFor = null): Response
    {
        $_SERVER['REMOTE_ADDR'] = $remoteAddr;

        if ($xForwardedFor !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $xForwardedFor;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        $middleware = new IpAllowlist();

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

        /** @var array<string, mixed> $serverArray */
        $serverArray = $_SERVER;

        $request = new Request(
            uri: '/api/v1/health',
            method: 'GET',
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $serverArray
        );

        return $middleware->process($request, $handler);
    }

    public function testDotenvInlineCommentsDoNotBreakIpAllowlistInProduction(): void
    {
        $envFile = $this->writeTempEnv(
            "APP_ENV=production # inline comment\n"
            . "HEALTH_IP_ALLOWLIST=203.0.113.5 # allow only this\n"
        );

        $dotenv = new Dotenv($envFile, null);
        $dotenv->load();

        $allowed = $this->runIpAllowlist('203.0.113.5');
        $this->assertSame(200, $allowed->getStatusCode(), 'Allowlisted IP ska tillåtas i production (laddat via Dotenv).');

        $blocked = $this->runIpAllowlist('203.0.113.6');
        $this->assertSame(403, $blocked->getStatusCode(), 'IP utanför allowlist ska blockeras i production (laddat via Dotenv).');
    }

    public function testDotenvInlineCommentsAllowBypassInDevelopment(): void
    {
        $envFile = $this->writeTempEnv(
            "APP_ENV=development # inline comment\n"
            . "HEALTH_IP_ALLOWLIST= # spelar ingen roll i development\n"
        );

        $dotenv = new Dotenv($envFile, null);
        $dotenv->load();

        $res = $this->runIpAllowlist('203.0.113.6');
        $this->assertSame(200, $res->getStatusCode(), 'I development ska middleware släppa igenom även utan allowlist (laddat via Dotenv).');
    }

    public function testDotenvInlineCommentsAllowBypassInLocal(): void
    {
        $envFile = $this->writeTempEnv(
            "APP_ENV=local # inline comment\n"
            . "HEALTH_IP_ALLOWLIST= # spelar ingen roll i local\n"
        );

        $dotenv = new Dotenv($envFile, null);
        $dotenv->load();

        $res = $this->runIpAllowlist('203.0.113.6');
        $this->assertSame(200, $res->getStatusCode(), 'I local ska middleware släppa igenom även utan allowlist (laddat via Dotenv).');
    }


    public function testDotenvInlineCommentsTrustedProxyUsesXForwardedForInProduction(): void
    {
        $envFile = $this->writeTempEnv(
            "APP_ENV=production # inline comment\n"
            . "TRUSTED_PROXY=10.0.0.1 # this proxy may set X-Forwarded-For\n"
            . "HEALTH_IP_ALLOWLIST=203.0.113.9 # allow client ip\n"
        );

        $dotenv = new Dotenv($envFile, null);
        $dotenv->load();

        // REMOTE_ADDR är proxyn, klient-IP ligger i X-Forwarded-For
        $res = $this->runIpAllowlist('10.0.0.1', '203.0.113.9, 192.168.1.1');
        $this->assertSame(200, $res->getStatusCode(), 'När TRUSTED_PROXY matchar ska första X-Forwarded-For-IP användas för allowlist.');
    }

    public function testDotenvInlineCommentsUntrustedProxyCannotSpoofXForwardedForInProduction(): void
    {
        $envFile = $this->writeTempEnv(
            "APP_ENV=production # inline comment\n"
            . "TRUSTED_PROXY=10.0.0.1 # only this proxy is trusted\n"
            . "HEALTH_IP_ALLOWLIST=203.0.113.9 # allow client ip\n"
        );

        $dotenv = new Dotenv($envFile, null);
        $dotenv->load();

        // REMOTE_ADDR är INTE betrodd proxy, men försöker spoof:a en tillåten XFF
        $res = $this->runIpAllowlist('10.0.0.2', '203.0.113.9');
        $this->assertSame(403, $res->getStatusCode(), 'Endast TRUSTED_PROXY ska få påverka klient-IP via X-Forwarded-For.');
    }
}
