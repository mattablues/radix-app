<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\Middlewares\RequestLogger;
use Radix\Session\SessionInterface;
use Radix\Support\Logger;
use RuntimeException;
use Throwable;

// Flytta spion-loggern till toppnivå (utanför testklassen) och behåll den som egen klass.
final class SpyLogger extends Logger
{
    /** @var array<int, array{msg: string, ctx: array<string,mixed>}> */
    public array $infos = [];

    public function __construct() {}

    public function info(string $message, array $context = []): void
    {
        $this->infos[] = ['msg' => $message, 'ctx' => $context];
    }

    public function error(string $message, array $context = []): void {}
    public function warning(string $message, array $context = []): void {}
    public function debug(string $message, array $context = []): void {}
}

final class RequestLoggerTest extends TestCase
{
    /**
     * @param array<string,string> $headers
     */
    private function makeRequest(
        string $uri = '/path',
        string $method = 'GET',
        array $headers = [],
        ?SessionInterface $session = null,
        string $ip = '127.0.0.1'
    ): Request {
        // Sätt server med relevant header-nycklar
        $server = [];
        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        // Radix\Http\Request konstruktor (namngivna argument för tydlighet)
        $request = new Request(
            uri: $uri,
            method: $method,
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $server
        );

        // Mocka ip(), header() och session() om nödvändigt
        // Skapa en partiell mock så vi kan stubba ip/header/session
        /** @var Request&\PHPUnit\Framework\MockObject\MockObject $reqMock */
        $reqMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([$uri, $method, [], [], [], [], $server])
            ->onlyMethods(['ip', 'header', 'session'])
            ->getMock();

        $reqMock->method('ip')->willReturn($ip);
        $reqMock->method('header')->willReturnCallback(
            /**
             * @return string|null
             */
            function (string $key) use ($headers): ?string {
                $k = strtolower($key);
                foreach ($headers as $hKey => $hVal) {
                    if (strtolower($hKey) === $k) {
                        return is_string($hVal) ? $hVal : null;
                    }
                }
                return null;
            }
        );
        if ($session !== null) {
            $reqMock->method('session')->willReturn($session);
        } else {
            /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $sess */
            $sess = $this->createMock(SessionInterface::class);
            $sess->method('get')->willReturn(null);
            $reqMock->method('session')->willReturn($sess);
        }

        return $reqMock;
    }

    private function makeHandlerReturning(int $status = 200): RequestHandlerInterface
    {
        return new class ($status) implements RequestHandlerInterface {
            public function __construct(private int $status) {}
            public function handle(Request $request): Response
            {
                $r = new Response();
                $r->setStatusCode($this->status);
                // Återanvänd path och method via Response-kropp för att undvika mutationer som tar bort läsning
                $r->setBody('ok');
                return $r;
            }
        };
    }

    private function makeHandlerThrowing(Throwable $e): RequestHandlerInterface
    {
        return new class ($e) implements RequestHandlerInterface {
            public function __construct(private Throwable $e) {}
            public function handle(Request $request): Response
            {
                throw $this->e;
            }
        };
    }

    public function testLogsInfoOnSuccessWithNormalizedContext(): void
    {
        $spy = new SpyLogger();

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        // Returnera 42 för AUTH_KEY och något annat för annan nyckel för att säkerställa korrekt guard
        $session->method('get')->willReturnCallback(
            /**
             * @param string $key
             * @return mixed
             */
            function (string $key) {
                if ($key === \Radix\Session\Session::AUTH_KEY) {
                    return 42;
                }
                return null;
            }
        );

        $req = $this->makeRequest(
            uri: '/api/items',
            method: 'POST',
            headers: [
                'X-Request-Id' => 'abc123',
                'User-Agent' => 'UnitTest-UA/1.0',
            ],
            session: $session,
            ip: '203.0.113.5'
        );

        $mw = new RequestLogger($spy, 'api');
        $resp = $mw->process($req, $this->makeHandlerReturning(201));

        $this->assertSame(201, $resp->getStatusCode());

        $this->assertNotEmpty($spy->infos);
        $entry = $spy->infos[0];

        $this->assertSame('{method} {path} -> {status} {ms}ms', $entry['msg']);

        $ctx = $entry['ctx'];
        // Obligatoriska fält
        $this->assertSame('POST', $ctx['method']);
        $this->assertSame('/api/items', $ctx['path']);
        $this->assertSame(201, $ctx['status']);
        $this->assertSame('203.0.113.5', $ctx['ip']);
        $this->assertSame('UnitTest-UA/1.0', $ctx['ua']);
        $this->assertSame(42, $ctx['userId']);
        $this->assertSame('abc123', $ctx['requestId']);
        $this->assertSame('api', $ctx['channel']);

        // Tidsfält: ms är heltal avrundat från us/1000, us är heltal >= 0
        $this->assertIsInt($ctx['us']);
        $this->assertIsInt($ctx['ms']);
        $this->assertGreaterThanOrEqual(0, $ctx['us']);
        $this->assertGreaterThanOrEqual(0, $ctx['ms']);

        // Dödar mutants: ändringar i avrundning eller skala
        // Kontrollera rimlighet och konsistens mellan ms och us (ms ~= round(us/1000))
        $expectedMs = (int) intdiv($ctx['us'] + 500, 1000);
        $this->assertSame($expectedMs, $ctx['ms']);
    }

    public function testLogsWithNullsWhenMissingHeadersAndNonIntUser(): void
    {
        $spy = new SpyLogger();

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        // Returnera icke-int för att verifiera att userId => null
        $session->method('get')->willReturn('not-int');

        // Saknar X-Request-Id och User-Agent
        $req = $this->makeRequest(
            uri: '/ping',
            method: 'GET',
            headers: [],
            session: $session,
            ip: '198.51.100.77'
        );

        $mw = new RequestLogger($spy); // default channel = http
        $resp = $mw->process($req, $this->makeHandlerReturning(200));

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertNotEmpty($spy->infos);

        $ctx = $spy->infos[0]['ctx'];

        $this->assertSame('GET', $ctx['method']);
        $this->assertSame('/ping', $ctx['path']);
        $this->assertSame(200, $ctx['status']);
        $this->assertSame('http', $ctx['channel']);

        // Normalisering
        $this->assertNull($ctx['requestId'], 'requestId ska vara null när header saknas eller tom');
        $this->assertSame('', $ctx['ua'], 'ua ska bli tom sträng när header saknas i koden');
        $this->assertNull($ctx['userId'], 'userId ska vara null när det inte är int');

        $this->assertIsInt($ctx['us']);
        $this->assertIsInt($ctx['ms']);
    }

    public function testLogsStatus500WhenHandlerThrowsButStillLogs(): void
    {
        $spy = new SpyLogger();

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(null);

        $req = $this->makeRequest(
            uri: '/fail',
            method: 'DELETE',
            headers: ['User-Agent' => 'TestUA'],
            session: $session,
            ip: '192.0.2.10'
        );

        $mw = new RequestLogger($spy, 'ops');

        $this->expectException(RuntimeException::class);
        try {
            $mw->process($req, $this->makeHandlerThrowing(new RuntimeException('boom')));
        } finally {
            // Trots exception ska loggen finnas och status=500
            $this->assertNotEmpty($spy->infos, 'Logger ska ha skrivits i finally-blocket.');
            $ctx = $spy->infos[0]['ctx'];
            $this->assertSame(500, $ctx['status']);
            $this->assertSame('ops', $ctx['channel']);
            $this->assertSame('DELETE', $ctx['method']);
            $this->assertSame('/fail', $ctx['path']);
            $this->assertSame('TestUA', $ctx['ua']);
        }
    }

    public function testRequestIdEmptyStringBecomesNull(): void
    {
        $spy = new SpyLogger();

        /** @var SessionInterface&\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(7);

        $req = $this->makeRequest(
            uri: '/rid',
            method: 'GET',
            headers: ['X-Request-Id' => ''], // tomt
            session: $session
        );

        $mw = new RequestLogger($spy);
        $mw->process($req, $this->makeHandlerReturning(204));

        $ctx = $spy->infos[0]['ctx'];
        $this->assertSame(204, $ctx['status']);
        $this->assertNull($ctx['requestId'], 'tom X-Request-Id ska normaliseras till null');
        $this->assertSame(7, $ctx['userId']);
    }

    public function testMonotonicTimingAndRoundingAgainstMutants(): void
    {
        $spy = new SpyLogger();

        $req = $this->makeRequest(uri: '/time', method: 'GET', headers: ['User-Agent' => 'UA']);
        $mw = new RequestLogger($spy);

        // Första körningen
        $resp1 = $mw->process($req, $this->makeHandlerReturning(200));
        $this->assertSame(200, $resp1->getStatusCode());
        $this->assertNotEmpty($spy->infos);
        /** @var array<string,mixed> $ctx1 */
        $ctx1 = $this->getFirstContext($spy);
        $this->assertIsInt($ctx1['us']);
        $this->assertIsInt($ctx1['ms']);
        /** @var int $us1 */
        $us1 = $ctx1['us'];
        $this->assertSame((int) intdiv($us1 + 500, 1000), $ctx1['ms']);

        usleep(20000); // 20ms

        // Andra körningen
        $spy->infos = [];
        $resp2 = $mw->process($req, $this->makeHandlerReturning(200));
        $this->assertSame(200, $resp2->getStatusCode());
        $this->assertNotEmpty($spy->infos);
        /** @var array<string,mixed> $ctx2 */
        $ctx2 = $this->getFirstContext($spy);

        // Monotonicitet och exakt formel
        $this->assertGreaterThanOrEqual($ctx1['us'], $ctx2['us'], 'us ska inte minska (dödar delta=end+start eller delning i stället för multiplikation)');
        $this->assertGreaterThanOrEqual($ctx1['ms'], $ctx2['ms'], 'ms ska inte minska (dödar felaktig avrundning eller delare 999/1001)');
        /** @var int $us2 */
        $us2 = $ctx2['us'];
        $this->assertSame((int) intdiv($us2 + 500, 1000), $ctx2['ms'], 'ms måste vara intdiv(us+500,1000)');

    }

    public function testTimingDeltaAndRoundingAreCorrect(): void
    {
        $spy = new SpyLogger();
        $mw = new RequestLogger($spy);

        $handler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                usleep(20000); // ~20ms
                $r = new Response();
                $r->setStatusCode(200);
                return $r;
            }
        };

        $req = $this->makeRequest(uri: '/timing', method: 'GET', headers: ['User-Agent' => 'UA'], session: null, ip: '127.0.0.1');

        // Körning 1
        $resp1 = $mw->process($req, $handler);
        $this->assertSame(200, $resp1->getStatusCode());
        $this->assertNotEmpty($spy->infos);
        /** @var array<string,mixed> $ctx1 */
        $ctx1 = $this->getFirstContext($spy);

        $this->assertIsInt($ctx1['us']);
        $this->assertIsInt($ctx1['ms']);
        /** @var int $us1 */
        $us1 = $ctx1['us'];
        $this->assertSame((int) intdiv($us1 + 500, 1000), $ctx1['ms']);
        $this->assertGreaterThanOrEqual(15_000, $ctx1['us']);
        $this->assertLessThan(200_000, $ctx1['us']);

        // Körning 2
        $spy->infos = [];
        $resp2 = $mw->process($req, $handler);
        $this->assertSame(200, $resp2->getStatusCode());
        $this->assertNotEmpty($spy->infos);
        /** @var array<string,mixed> $ctx2 */
        $ctx2 = $this->getFirstContext($spy);

        /** @var int $us2 */
        $us2 = $ctx2['us'];
        $this->assertSame((int) intdiv($us2 + 500, 1000), $ctx2['ms']);

        // us kan fluktuera lite; tillåt 10% minskning ...
        $this->assertGreaterThanOrEqual((int) floor($ctx1['us'] * 0.9), $ctx2['us'], 'us ska inte minska väsentligt mellan körningar');
        $this->assertGreaterThanOrEqual($ctx1['ms'], $ctx2['ms'], 'ms ska inte minska mellan körningar');
    }

    public function testRoundingAtHalfBoundariesForUsAndMs(): void
    {
        $spy = new SpyLogger();
        $mw = new RequestLogger($spy);

        // Handler som låter oss simulera ett "delta" runt halv-gränser med busy-wait
        $handler = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                // Vi vill nå minst ~0.5ms (500us) för att testa avrundning.
                // Busy-wait tills en liten tid passerat.
                $tStart = microtime(true);
                do {
                    $now = microtime(true);
                } while (($now - $tStart) < 0.0006); // ~600us

                $r = new Response();
                $r->setStatusCode(200);
                return $r;
            }
        };

        $req = $this->makeRequest(uri: '/rounding', method: 'GET', headers: ['User-Agent' => 'UA']);

        $mw->process($req, $handler);
        $this->assertNotEmpty($spy->infos);
        /** @var array<string,mixed> $ctx */
        $ctx = $this->getFirstContext($spy);

        $this->assertIsInt($ctx['us']);
        $this->assertIsInt($ctx['ms']);

        $expectedMs = (int) intdiv($ctx['us'] + 500, 1000);
        $this->assertSame($expectedMs, $ctx['ms'], 'ms måste vara intdiv(us+500,1000) (dödar +499-mutation)');
        $this->assertGreaterThanOrEqual(1, $ctx['ms'], 'ms får inte bli 0 när us ~600us (dödar -0.5-mutation)');
    }

    /**
     * Hämta första loggpostens context på ett sätt som PHPStan förstår.
     *
     * @return array<string,mixed>
     */
    private function getFirstContext(SpyLogger $spy): array
    {
        /** @var array<int, array{msg: string, ctx: array<string,mixed>}> $infos */
        $infos = $spy->infos;

        if ($infos === []) {
            $this->fail('SpyLogger::infos är tomt – ingen loggrad att inspektera');
        }

        $first = $infos[0];

        return $first['ctx'];
    }
}
