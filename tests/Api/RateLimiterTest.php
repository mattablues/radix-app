<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use PHPUnit\Framework\TestCase;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\Middlewares\RateLimiter;
use Radix\Support\FileCache;

final class RateLimiterTest extends TestCase
{
    private function makeHandler(int $status = 200): RequestHandlerInterface
    {
        return new class ($status) implements RequestHandlerInterface {
            public function __construct(private int $status) {}
            public function handle(Request $request): Response
            {
                $r = new Response();
                $r->setStatusCode($this->status);
                return $r;
            }
        };
    }

    private function makeRequest(string $ip = '203.0.113.10'): Request
    {
        $server = ['REMOTE_ADDR' => $ip];

        /** @var Request&\PHPUnit\Framework\MockObject\MockObject $req */
        $req = $this->getMockBuilder(Request::class)
            ->setConstructorArgs(['/test', 'GET', [], [], [], [], $server])
            ->onlyMethods(['ip'])
            ->getMock();

        $req->method('ip')->willReturn($ip);

        return $req;
    }

    public function testAllowsRequestsUntilLimitAndSetsHeaders(): void
    {
        $limit = 3;
        $window = 2;
        $bucket = 't1';

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);

        $handler = $this->makeHandler(200);
        $req = $this->makeRequest();

        $t0 = time();
        $res1 = $mw->process($req, $handler);
        $headers1 = $res1->headers();
        $this->assertSame(200, $res1->getStatusCode());
        $this->assertSame((string) $limit, $headers1['X-RateLimit-Limit'] ?? null);
        $this->assertSame((string) ($limit - 1), $headers1['X-RateLimit-Remaining'] ?? null);
        $resetAt1 = (int) ($headers1['X-RateLimit-Reset'] ?? 0);
        $this->assertGreaterThanOrEqual($t0, $resetAt1);
        // Verifiera exakt resetAt-beräkning
        $expectedWindowId = (int) floor($t0 / $window);
        $this->assertSame(($expectedWindowId + 1) * $window, $resetAt1);

        $res2 = $mw->process($req, $handler);
        $this->assertSame(200, $res2->getStatusCode());
        $this->assertSame((string) ($limit - 2), $res2->headers()['X-RateLimit-Remaining'] ?? null);

        $res3 = $mw->process($req, $handler);
        $this->assertSame(200, $res3->getStatusCode());
        $this->assertSame('0', $res3->headers()['X-RateLimit-Remaining'] ?? null);
    }

    public function testReturns429WhenExceedingLimitAndSetsRetryHeaders(): void
    {
        $limit = 2;
        $window = 3;
        $bucket = 't2';

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);

        $handler = $this->makeHandler(200);
        $req = $this->makeRequest('198.51.100.77');

        // Förbruka limit
        $mw->process($req, $handler);
        $mw->process($req, $handler);

        $tNow = time();
        $res = $mw->process($req, $handler);
        $headers = $res->headers();

        $this->assertSame(429, $res->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $headers['Content-Type'] ?? null);
        $this->assertSame((string) $limit, $headers['X-RateLimit-Limit'] ?? null);
        $this->assertSame('0', $headers['X-RateLimit-Remaining'] ?? null);

        $resetAt = (int) ($headers['X-RateLimit-Reset'] ?? 0);
        $retryAfter = (int) ($headers['Retry-After'] ?? 0);

        // X-RateLimit-Reset måste vara satt och >= nu (dödar MethodCallRemoval-mutanter)
        $this->assertGreaterThan(0, $resetAt, 'X-RateLimit-Reset måste vara satt i 429-svaret');
        $this->assertGreaterThanOrEqual($tNow, $resetAt, 'X-RateLimit-Reset får inte ligga i det förflutna');

        // Retry-After ska vara minst 1 och = max(1, resetAt - now)
        $this->assertGreaterThanOrEqual(1, $retryAfter);
        $this->assertSame(max(1, $resetAt - $tNow), $retryAfter);

        // JSON ska vara giltig och innehålla felobjekt (dödar &-mutation av JSON-flaggor)
        $body = (string) $res->getBody();
        $this->assertStringContainsString('too_many_requests', $body);
        $this->assertStringContainsString('Rate limit exceeded', $body);
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('error', $decoded);
    }

    public function testSeparateIpHaveSeparateBuckets(): void
    {
        $limit = 1;
        $window = 5;
        $bucket = 't3';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();

        $reqA = $this->makeRequest('203.0.113.1');
        $reqB = $this->makeRequest('203.0.113.2');

        $resA1 = $mw->process($reqA, $handler);
        $this->assertSame(200, $resA1->getStatusCode());
        $this->assertSame('0', $resA1->headers()['X-RateLimit-Remaining'] ?? null);

        $resB1 = $mw->process($reqB, $handler);
        $this->assertSame(200, $resB1->getStatusCode());
        $this->assertSame('0', $resB1->headers()['X-RateLimit-Remaining'] ?? null);

        $resA2 = $mw->process($reqA, $handler);
        $this->assertSame(429, $resA2->getStatusCode());
    }

    public function testNewWindowResetsCounterDueToTtlLogicAndRemainingNeverNegative(): void
    {
        $limit = 1;
        $window = 2;
        $bucket = 't4';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest();

        $res1 = $mw->process($req, $handler);
        $this->assertSame(200, $res1->getStatusCode());
        $this->assertSame('0', $res1->headers()['X-RateLimit-Remaining'] ?? null);

        $res2 = $mw->process($req, $handler);
        if ($res2->getStatusCode() !== 429) {
            usleep(300000);
            $res2 = $mw->process($req, $handler);
        }
        $this->assertSame(429, $res2->getStatusCode());

        // remaining får aldrig vara negativ
        $this->assertNotSame('-1', $res1->headers()['X-RateLimit-Remaining'] ?? null);

        sleep($window + 1);

        $res3 = $mw->process($req, $handler);
        $this->assertSame(200, $res3->getStatusCode());
        $this->assertSame('0', $res3->headers()['X-RateLimit-Remaining'] ?? null);
    }

    public function testBucketDefaultAppliedWhenNull(): void
    {
        $limit = 1;
        $window = 5;

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mwNull = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: null, fileCache: $cache);
        $mwDefault = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: 'default', fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.55');

        // Första i null-bucket
        $t0 = time();
        $r1 = $mwNull->process($req, $handler);
        $this->assertSame(200, $r1->getStatusCode());
        $resetNull = (int) ($r1->headers()['X-RateLimit-Reset'] ?? 0);
        $expectedWindowId = (int) floor($t0 / $window);
        $expectedResetAt = ($expectedWindowId + 1) * $window;
        $this->assertSame($expectedResetAt, $resetNull, 'ResetAt ska följa floor-formeln (dödar castInt)');

        // Andra i null-bucket -> 429
        $this->assertSame('0', $r1->headers()['X-RateLimit-Remaining'] ?? null);
        $r2 = $mwNull->process($req, $handler);
        $this->assertSame(429, $r2->getStatusCode());

        // Explicit default ska dela samma bucket och ge 429 direkt (dödar ternary)
        $r3 = $mwDefault->process($req, $handler);
        $this->assertSame(429, $r3->getStatusCode(), 'Null och "default" ska dela bucket');
    }

    public function testWindowIdUsesFloorDivisionNotCeilOrRound(): void
    {
        // Litet fönster för att göra fönstergränser tydliga
        $limit = 2;
        $window = 1;
        $bucket = 'tFloor';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);
        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest();

        // Två snabba anrop inom samma sekund ska få samma resetAt om floor används
        $res1 = $mw->process($req, $handler);
        $res2 = $mw->process($req, $handler);

        $reset1 = (int) ($res1->headers()['X-RateLimit-Reset'] ?? 0);
        $reset2 = (int) ($res2->headers()['X-RateLimit-Reset'] ?? 0);

        $this->assertSame($reset1, $reset2, 'Reset-tid ska vara identisk inom samma fönster (dödar ceil/round-mutanter)');
    }

    public function testFileCachePathConstructionUsesSysTempDirWithSeparator(): void
    {
        $expectedDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'radix_ratelimit';
        if (is_dir($expectedDir)) {
            foreach (glob($expectedDir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
                @unlink($f);
            }
        }

        $limit = 1;
        $window = 60;
        $bucket = 'tPath';
        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.200');

        $res = $mw->process($req, $handler);
        $this->assertSame(200, $res->getStatusCode());

        $expectedDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'radix_ratelimit';
        $this->assertDirectoryExists($expectedDir, 'Filcachekatalogen ska ligga under sys_get_temp_dir()/radix_ratelimit');

        // Minst en cachefil ska finnas i katalogen – fångar concat-mutanter som pekar på fel plats
        $filesInExpected = glob($expectedDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
        $this->assertNotEmpty($filesInExpected, 'Ingen cachefil i korrekt katalog');
    }

    public function testTtlResetsCounterAtWindowBoundary(): void
    {
        $limit = 1;
        $window = 1; // mycket kort TTL
        $bucket = 'tTTL';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);
        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.210');

        // Första request inom fönstret
        $r1 = $mw->process($req, $handler);
        $this->assertSame(200, $r1->getStatusCode());

        // Andra inom samma fönster -> 429
        $r2 = $mw->process($req, $handler);
        if ($r2->getStatusCode() !== 429) {
            // Om fönstret slog över exakt, gör om snabbt
            $r2 = $mw->process($req, $handler);
        }
        $this->assertSame(429, $r2->getStatusCode());

        // Vänta lite mer än 1s för att TTL ska löpa ut (dödar max(0, ...) och + now mutationer)
        usleep(1_200_000); // 1.2s

        // Nu ska räknaren vara nollad av TTL -> 200 igen och remaining=0
        $r3 = $mw->process($req, $handler);
        $this->assertSame(200, $r3->getStatusCode());
        $this->assertSame('0', $r3->headers()['X-RateLimit-Remaining'] ?? null);
    }

    public function testDefaultLimitIs60AndCacheWrittenInExpectedTempDir(): void
    {
        // Använd default-konstruktorn (limit=60, window=60, bucket=null => 'default')
        $mw = new RateLimiter(); // inga argument
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.250');

        $firstResetAt = null;

        // Förbruka exakt 60 requests inom samma fönster
        for ($i = 1; $i <= 60; $i++) {
            $res = $mw->process($req, $handler);
            $this->assertSame(200, $res->getStatusCode(), "Request #$i ska vara 200 med default limit 60");

            $headers = $res->headers();
            $resetAt = isset($headers['X-RateLimit-Reset']) ? (int) $headers['X-RateLimit-Reset'] : 0;

            if ($firstResetAt === null && $resetAt > 0) {
                $firstResetAt = $resetAt;
            }
        }

        // 61:a ska ge 429 om vi fortfarande är i samma fönster.
        // Om fönstret precis slog över är 200 acceptabelt och säger inget om limit-logiken.
        $nowBefore61 = time();
        $res61 = $mw->process($req, $handler);
        $status61 = $res61->getStatusCode();

        if ($firstResetAt !== null && $nowBefore61 < $firstResetAt) {
            $this->assertSame(
                429,
                $status61,
                'Request #61 ska returnera 429 med default limit 60 när vi är kvar i samma fönster.'
            );
        } else {
            // Vi har gått in i nytt fönster – då kan #61 mycket väl vara 200.
            $this->assertSame(
                200,
                $status61,
                'Request #61 hamnade i nytt fönster och ska då kunna passera.'
            );
        }

        // Cachefiler ska finnas i sys temp dir under radix_ratelimit
        $expectedDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'radix_ratelimit';
        $this->assertDirectoryExists($expectedDir, 'Filcachekatalogen ska ligga under sys_get_temp_dir()/radix_ratelimit');
        $files = glob($expectedDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
        $this->assertNotEmpty($files, 'Ingen cachefil i korrekt katalog – concat-mutanter ska falla');
    }

    public function testWindowIdFloorFormulaHoldsAndDoesNotUseCeil(): void
    {
        $limit = 3;
        $window = 2;
        $bucket = 'ceilCheck';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.66');

        $t0 = time();
        $r1 = $mw->process($req, $handler);
        $reset1 = (int) ($r1->headers()['X-RateLimit-Reset'] ?? 0);

        // ResetAt måste följa floor(time()/window)*window + window
        $expectedWindowId = (int) floor($t0 / $window);
        $expectedResetAt = ($expectedWindowId + 1) * $window;
        $this->assertContains($reset1, [$expectedResetAt, $expectedResetAt + 1], 'ResetAt ska följa floor-formeln (inte ceil)');

        // Andra anrop inom samma fönster ska inte ge hoppsan-hopp (ceil)
        usleep(200_000); // 0.2s
        $r2 = $mw->process($req, $handler);
        $reset2 = (int) ($r2->headers()['X-RateLimit-Reset'] ?? 0);

        // Beräkna vilket fönster reset1 och reset2 faktiskt tillhör
        $windowId1FromReset = (int) floor($reset1 / $window);
        $windowId2FromReset = (int) floor($reset2 / $window);

        if ($windowId1FromReset === $windowId2FromReset) {
            // Endast om båda svaren ligger i samma fönster testar vi "ingen ceil-hoppsan"
            $this->assertContains(
                $reset2,
                [$reset1, $reset1 + 1],
                'ResetAt ska inte hoppa p.g.a. ceil inom samma fönster'
            );
        }
        // Om vi korsat fönstergräns är det inte ett ceil-fel, så vi gör ingen extra assert här.
    }

    public function testCacheFileForCurrentKeyIsCreatedInExpectedTempDir(): void
    {
        // Skapa en unik bucket/ip så vi kan matcha specifikt filnamn
        $bucket = 'pathCheck_' . bin2hex(random_bytes(3));
        $ip = '203.0.113.199';

        $mw = new RateLimiter(redis: null, windowSeconds: 60, bucket: $bucket); // default limit 60
        $handler = $this->makeHandler();
        $req = $this->makeRequest($ip);

        // Trigga skrivning (minst 1 request)
        $res = $mw->process($req, $handler);
        $this->assertSame(200, $res->getStatusCode());

        $expectedDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'radix_ratelimit';
        $this->assertDirectoryExists($expectedDir);

        // Matcha just vår nyckel i korrekt katalog
        $all = glob($expectedDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
        $pattern = sprintf('#ratelimit_%s_%s_\d+\.cache$#', preg_quote($bucket, '#'), preg_quote($ip, '#'));
        $matches = array_values(array_filter($all, fn(string $f) => preg_match($pattern, str_replace('\\', '/', $f)) === 1));
        $this->assertNotEmpty($matches, 'Cachefil för aktuell nyckel saknas i expectedDir (fångar concat-mutanter)');
    }

    public function testExpireAtUsesNowPlusTtlSoCounterResetsAfterWindow(): void
    {
        // Litet fönster så TTL hinner löpa ut
        $limit = 1;
        $window = 1;
        $bucket = 'ttlExpireAtCheck_' . uniqid('', true);

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.123');

        // Första anrop inom fönstret -> 200
        $r1 = $mw->process($req, $handler);
        $this->assertSame(200, $r1->getStatusCode());

        // Andra inom samma fönster -> 429
        $r2 = $mw->process($req, $handler);
        if ($r2->getStatusCode() !== 429) {
            // Om fönstret precis slog över, trigga igen snabbt
            $r2 = $mw->process($req, $handler);
        }
        $this->assertSame(429, $r2->getStatusCode(), 'Andra anrop inom fönstret ska rate-limitas');

        // Vänta längre än TTL så att räknaren måste nollas om expireAt = now + ttl
        usleep(1_200_000); // ~1.2s

        // Nytt fönster -> 200 igen (faller om expireAt var now - ttl)
        $r3 = $mw->process($req, $handler);
        $this->assertSame(200, $r3->getStatusCode(), 'Efter TTL ska fönstret ha nollats');
        $this->assertSame('0', $r3->headers()['X-RateLimit-Remaining'] ?? null);
    }

    public function testDefaultConstructorUsesWindow60AndValidatesRedisWiring(): void
    {
        $mw = new RateLimiter(); // default limit=60, window=60, bucket=null
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.88');

        $t0 = time();
        $res = $mw->process($req, $handler);
        $this->assertSame(200, $res->getStatusCode());

        $resetAt = (int) ($res->headers()['X-RateLimit-Reset'] ?? 0);
        $expectedWindowId = (int) floor($t0 / 60);
        $this->assertSame(($expectedWindowId + 1) * 60, $resetAt, 'windowSeconds måste vara exakt 60');

        // Andra request inom samma fönster ska minska remaining med exakt 1 (fångar felaktig redis-branch)
        $res2 = $mw->process($req, $handler);
        $this->assertSame(200, $res2->getStatusCode());
        $this->assertSame('58', $res2->headers()['X-RateLimit-Remaining'] ?? null);
    }

    public function testFileCachePathRtrimAndTtlAndJsonFlagsAreExact(): void
    {
        $expectedDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'radix_ratelimit';
        if (is_dir($expectedDir)) {
            foreach (glob($expectedDir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
                @unlink($f);
            }
        }

        $mw = new RateLimiter(redis: null, limit: 1, windowSeconds: 2, bucket: 'mut-kill');
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.180');

        // Första request -> ska vara 200 (initial request should work)
        $r1 = $mw->process($req, $handler);
        $this->assertSame(200, $r1->getStatusCode(), 'Första request misslyckades');

        // Kontrollera att cache-filen finns (Verify the cache file's presence)
        $this->assertDirectoryExists($expectedDir, 'Cachekatalogen finns inte');

        // Andra request -> ska vara 429 (Second request should hit limit)
        $r2 = $mw->process($req, $handler);
        $body = (string) $r2->getBody();
        $this->assertSame(429, $r2->getStatusCode(), 'Andra request returnerade inte 429');

        $reset2 = (int) ($r2->headers()['X-RateLimit-Reset'] ?? 0);

        // Kontrollera TTL-logiken (<1 sekunder senare). Simulate a request after 500ms.
        usleep(500_000); // sleep for 0.5 seconds
        $r3 = $mw->process($req, $handler);

        $reset3 = (int) ($r3->headers()['X-RateLimit-Reset'] ?? 0);

        if ($reset3 === $reset2) {
            // Samma fönster -> vi förväntar oss fortfarande 429
            $this->assertSame(429, $r3->getStatusCode(), 'TTL på 500ms verkar vara felaktigt');
        } else {
            // Vi har korsat ett riktigt fönstergränssnitt – då kan 200 vara korrekt.
            // Inga extra asserts här för att undvika flakyness.
        }

        // Vänta tills TTL/fönster löper ut. För att undvika flakiness loopar vi upp till ~5s
        // och kräver att vi ser ett 200-svar inom rimlig tid.
        $deadline = microtime(true) + 5.0;
        $finalResponse = null;
        do {
            usleep(300_000); // 0.3s
            $finalResponse = $mw->process($req, $handler);
            if ($finalResponse->getStatusCode() === 200) {
                break;
            }
        } while (microtime(true) < $deadline);

        $this->assertSame(
            200,
            $finalResponse->getStatusCode(),
            'TTL-expiration verkar felaktig – status blev inte 200 inom rimlig tid'
        );
    }

    public function testTtlDoesNotRenewWithinExistingPeriod(): void
    {
        $limit = 1;
        $window = 2; // Kort fönster
        $bucket = 'ttl_test_' . uniqid('', true);

        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.180');

        // Första anrop -> skapa TTL
        $t0 = time();
        $mw->process($req, $handler);

        // Använd samma windowId-beräkning som RateLimiter
        $windowId = (int) floor($t0 / $window);
        $key = sprintf('ratelimit:%s:%s:%d', $bucket, '203.0.113.180', $windowId);

        /** @var array{c:int,e:int}|null $payload */
        $payload = $cache->get($key);
        $initialExpireAt = $payload['e'] ?? null;

        $this->assertNotNull($initialExpireAt, 'Initial TTL kunde inte läsas från cache');

        // Andra inom samma fönster -> TTL ska EJ ändras
        usleep(500_000); // 0.5 sekunder
        $mw->process($req, $handler);

        /** @var array{c:int,e:int}|null $payloadAfter */
        $payloadAfter = $cache->get($key);

        // I långsamma miljöer (Infection + Xdebug) kan posten ha hunnit löpa ut helt.
        // Om den fortfarande finns kvar ska TTL (e) vara oförändrad.
        if ($payloadAfter !== null && array_key_exists('e', $payloadAfter)) {
            $this->assertSame(
                $initialExpireAt,
                $payloadAfter['e'],
                'TTL ska inte förnyas innan det löper ut'
            );
        }

        // Vänta tills TTL/fönstret löper ut -> Då ska ny TTL skapas
        usleep(2_100_000); // Vänta >2 sekunder
        $mw->process($req, $handler);

        // Nu är vi sannolikt i nästa windowId, så beräkna om windowId baserat på ny tid
        $tLater = time();
        $newWindowId = (int) floor($tLater / $window);
        $newKey = sprintf('ratelimit:%s:%s:%d', $bucket, '203.0.113.180', $newWindowId);

        /** @var array{c:int,e:int}|null $payloadNew */
        $payloadNew = $cache->get($newKey);
        $this->assertNotNull($payloadNew, 'Ny TTL kunde inte läsas från cache');

        $this->assertGreaterThan(
            $initialExpireAt,
            $payloadNew['e'],
            'Ny TTL ska skapas efter utgången av den tidigare perioden'
        );
    }

    public function testEffectiveTtlCalculation(): void
    {
        $limit = 1;
        $window = 2;
        $bucket = 'ttl-calculation-test';
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.180');

        $start = time();
        $mw->process($req, $handler); // Utför första anropet

        /** @var array{c:int,e:int}|null $payload */
        $payload = $cache->get(sprintf('ratelimit:%s:%s:%d', $bucket, '203.0.113.180', floor($start / $window)));

        // Kontrollera att TTL är max(1, $expireAt - $now)
        $eRaw = $payload['e'] ?? 0;
        $expectedExpire = (int) $eRaw;
        $ttlUsed = max(1, $expectedExpire - $start);
        $this->assertGreaterThanOrEqual(1, $ttlUsed, 'TTL ska vara minst 1 sekund.');
        $this->assertEquals($expectedExpire, $start + $ttlUsed, 'TTL och expiration ska aligna korrekt.');
    }

    public function testCacheAlwaysStoresCountAndExpireAt(): void
    {
        $limit = 1;
        $window = 2;
        $bucket = 'cache-data-test_' . uniqid('', true);
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'radix_ratelimit_test_' . uniqid();
        @mkdir($cacheDir, 0o777, true);
        $cache = new FileCache($cacheDir);

        $mw = new RateLimiter(redis: null, limit: $limit, windowSeconds: $window, bucket: $bucket, fileCache: $cache);
        $handler = $this->makeHandler();
        $req = $this->makeRequest('203.0.113.180');

        // Skapa cache-data
        $mw->process($req, $handler);
        $key = sprintf('ratelimit:%s:%s:%d', $bucket, '203.0.113.180', floor(time() / $window));

        /** @var array{c:int,e:int}|null $payload */
        $payload = $cache->get($key);
        $this->assertNotEmpty($payload, 'Cache-data kan inte vara tomt.');
        $this->assertIsArray($payload);

        /** @var array{c:int,e:int} $payload */
        $payload = $payload;

        $this->assertArrayHasKey('c', $payload, 'Cache måste innehålla räknaren.');
        $this->assertArrayHasKey('e', $payload, 'Cache måste innehålla expiration-tiden.');
    }

    public function testRatelimitCachePathEnvOverridesDefaultLocation(): void
    {
        // Spara ev. tidigare värde för att kunna återställa
        $originalEnv = getenv('RATELIMIT_CACHE_PATH') === false
            ? null
            : (string) getenv('RATELIMIT_CACHE_PATH');

        // Skapa en unik katalog för detta test
        $customDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'ratelimit_env_test_' . uniqid();
        @mkdir($customDir, 0o777, true);

        // Sätt env-variabeln så att RateLimiter ska använda denna katalog
        putenv('RATELIMIT_CACHE_PATH=' . $customDir);

        try {
            // Använd default-konstruktorn (ingen FileCache injicerad)
            $mw = new RateLimiter(); // limit=60, window=60, bucket=null => 'default'
            $handler = $this->makeHandler();
            $req = $this->makeRequest('203.0.113.42');

            // Trigga minst en skrivning
            $res = $mw->process($req, $handler);
            $this->assertSame(200, $res->getStatusCode());

            // Verifiera att katalogen från env används och innehåller minst en .cache-fil
            $this->assertDirectoryExists($customDir, 'RATELIMIT_CACHE_PATH-katalogen måste existera');
            $files = glob($customDir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
            $this->assertNotEmpty(
                $files,
                'Ingen cachefil hittades i RATELIMIT_CACHE_PATH-katalogen – env ska override:a default-lokationen'
            );
        } finally {
            // Städa env efter testet
            if ($originalEnv === null) {
                putenv('RATELIMIT_CACHE_PATH');
            } else {
                putenv('RATELIMIT_CACHE_PATH=' . $originalEnv);
            }
        }
    }

}
