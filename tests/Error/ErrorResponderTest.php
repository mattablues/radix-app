<?php

declare(strict_types=1);

namespace Radix\Tests\Error;

use PHPUnit\Framework\TestCase;
use Radix\Error\ErrorResponder;
use Radix\Http\Request;

final class ErrorResponderTest extends TestCase
{
    private string $testViewDir;
    private int $startObLevel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->startObLevel = ob_get_level();

        // Skapa en temporär mapp för test-vyer för att undvika att ladda de riktiga tunga vyerna
        $this->testViewDir = __DIR__ . '/temp_error_views';
        if (!is_dir($this->testViewDir)) {
            mkdir($this->testViewDir, 0o777, true);
        }

        // Berätta för ErrorResponder att använda test-mappen (vi behöver lägga till denna statiska variabel)
        ErrorResponder::$viewPath = $this->testViewDir;

        // Skapa extremt enkla dummy-filer som inte kräver några globala funktioner
        file_put_contents($this->testViewDir . '/404.php', '<html>Test 404: <?php echo $errorMessage; ?></html>');
        file_put_contents($this->testViewDir . '/500.php', '<html>Test 500: <?php echo $errorStatus; ?></html>');

        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__, 2));
        }
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->startObLevel) {
            ob_end_clean();
        }

        ErrorResponder::$viewPath = null;
        if (is_dir($this->testViewDir)) {
            $files = glob($this->testViewDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->testViewDir);
        }
        parent::tearDown();
    }

    /**
     * Hjälpare för att skapa Request med korrekt signatur.
     *
     * @param array<string,mixed> $server
     */
    private function makeRequest(string $uri, string $method = 'GET', array $server = []): Request
    {
        /** @var array<string,mixed> $get */
        $get = [];
        /** @var array<string,mixed> $post */
        $post = [];
        /** @var array<string,mixed> $files */
        $files = [];
        /** @var array<string,mixed> $cookie */
        $cookie = [];

        return new Request(
            uri: $uri,
            method: $method,
            get: $get,
            post: $post,
            files: $files,
            cookie: $cookie,
            server: $server,
        );
    }

    /**
     * API‑anrop med korrekt prefix ska ge JSON och Content‑Type application/json.
     *
     * Dödar bl.a:
     * - [M] Identical (=== -> !==)
     * - [M] IfNegation på $isApi
     */
    public function testRespondForApiRequestReturnsJsonResponse(): void
    {
        $request   = $this->makeRequest('/api/v1/users', 'GET');
        $status    = 400;
        $message   = 'Bad request';
        $jsonExtra = ['foo' => 'bar'];

        $response = ErrorResponder::respond($request, $status, $message, $jsonExtra);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'application/json; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body    = (string) $response->getBody();
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertSame($message, $decoded['error'] ?? null);
        $this->assertSame($status, $decoded['status'] ?? null);
        $this->assertSame('bar', $decoded['foo'] ?? null);
    }

    /**
     * En URL som INTE börjar med /api/... men innehåller /api/... senare
     * ska behandlas som web (HTML), inte API.
     *
     * Dödar:
     * - [M] PregMatchRemoveCaret (borttagen ^ i regexen)
     */
    public function testRespondDoesNotTreatNonRootedApiPathAsApi(): void
    {
        $request = $this->makeRequest('/foo/api/v1/users', 'GET');
        $status  = 404;
        $message = 'Not found';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null,
            'En icke-rootad /api-path ska behandlas som HTML/web.'
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);
        $this->assertStringContainsString((string) $status, $body);
    }

    /**
     * När en specifik vy finns (t.ex. 404.php) ska den användas i första hand.
     *
     * Dödar:
     * - Concat/ConcatOperandRemoval‑mutanter för $errorFile
     * - IfNegation på is_file($errorFile)
     * - Ternary‑mutanten genom att säkerställa att vi får exakt filens innehåll
     * - MethodCallRemoval på setBody i web‑grenen
     *
     * OBS: förutsätter att ROOT_PATH . "/views/errors/404.php" finns.
     */
    public function testRespondWebUsesSpecificErrorViewWhenItExists(): void
    {
        $request = $this->makeRequest('/some/normal/page', 'GET');
        $status  = 404;
        $message = 'Page not found';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);

        // Förväntar oss att den specifika 404-vyn används, inte fallback eller default-h1.
        $this->assertStringContainsString('404', $body);
        $this->assertStringNotContainsString(
            "<h1>{$status} | {$message}</h1>",
            $body,
            'Body ska INTE vara default-h1 när en vyfil finns.'
        );
    }

    /**
     * När specifik vy inte finns ska fallback‑filen (t.ex. 500.php) användas.
     *
     * Dödar:
     * - Concat/ConcatOperandRemoval för $fallback
     * - IfNegation på is_file‑villkoret
     * - Ternary‑mutanten (vi säkerställer att fallback‑view används, inte default-h1)
     *
     * Välj en statuskod där du inte har en vyfil (t.ex. 599 eller 418),
     * men där ROOT_PATH . "/views/errors/500.php" finns.
     */
    public function testRespondWebFallsBackTo500ViewWhenSpecificViewDoesNotExist(): void
    {
        $request = $this->makeRequest('/another/normal/page', 'GET');
        $status  = 599;
        $message = 'Unexpected error';

        $response = ErrorResponder::respond($request, $status, $message);

        $this->assertSame($status, $response->getStatusCode());

        $headers = $response->headers();
        $this->assertSame(
            'text/html; charset=UTF-8',
            $headers['Content-Type'] ?? null
        );

        $body = (string) $response->getBody();
        $this->assertNotSame('', $body);

        // Antag att 500-vyn har något unikt (t.ex. "500" i HTML:en)
        $this->assertStringContainsString('500', $body);

        $this->assertStringNotContainsString(
            "<h1>{$status} | {$message}</h1>",
            $body,
            'Fallback-svaret ska komma från 500.php, inte från default-h1‑template.'
        );
    }

    public function testRespondUsesCorrectDefaultPathLogic(): void
    {
        $request = $this->makeRequest('/test');
        $oldPath = ErrorResponder::$viewPath;
        ErrorResponder::$viewPath = null;

        $realErrorDir = ROOT_PATH . '/views/errors';
        if (!is_dir($realErrorDir)) {
            mkdir($realErrorDir, 0o777, true);
        }
        $testFile = $realErrorDir . '/404.php';
        file_put_contents($testFile, 'DEFAULT_PATH_CONTENT');

        try {
            $response = ErrorResponder::respond($request, 404, 'msg');
            $this->assertSame('DEFAULT_PATH_CONTENT', (string) $response->getBody());
        } finally {
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            ErrorResponder::$viewPath = $oldPath;
        }
    }

    public function testRespondStateAndBufferIntegrityModern(): void
    {
        $request = $this->makeRequest('/test');
        $levelBefore = ob_get_level();

        // 1. Döda UnwrapRtrim (Rad 41) via space suffix
        $oldPath = ErrorResponder::$viewPath;
        // Vi lägger till ett blanksteg. Utan rtrim kommer is_file() misslyckas på CI.
        ErrorResponder::$viewPath = $this->testViewDir . ' ';

        file_put_contents($this->testViewDir . '/404.php', '<html>Test 404: msg</html>');

        $response = ErrorResponder::respond($request, 404, 'msg');

        // Om rtrim fungerar: vi får filinnehåll. Om det muteras bort: vi får <h1> fallback.
        $this->assertStringContainsString('Test 404: msg', (string) $response->getBody(), 'rtrim() muterades bort!');
        ErrorResponder::$viewPath = $oldPath;

        // 2. Döda CastString (Rad 60) via tom vy
        file_put_contents($this->testViewDir . '/404.php', '<?php /* Tom */ ?>');
        $response = ErrorResponder::respond($request, 404, 'EmptyTest');
        $this->assertSame('<h1>404 | EmptyTest</h1>', (string) $response->getBody());

        // 3. Döda UnwrapTrim (Rad 68) via whitespace
        file_put_contents($this->testViewDir . '/404.php', '   ');
        $response = ErrorResponder::respond($request, 404, 'Whitespace');
        $this->assertSame('<h1>404 | Whitespace</h1>', (string) $response->getBody());

        // 4. Verifiera krasch-hantering
        file_put_contents($this->testViewDir . '/404.php', '<?php throw new \Exception("Fail"); ?>');
        $response = ErrorResponder::respond($request, 404, 'Crash');
        $this->assertSame('<h1>404 | Crash</h1>', (string) $response->getBody());
        $this->assertSame($levelBefore, ob_get_level());
    }

    /**
     * Dödar CastString (Rad 59): Säkerställer sträng-integritet.
     */
    public function testRespondHandlesOutputBufferAsExplicitString(): void
    {
        $request = $this->makeRequest('/test');
        file_put_contents($this->testViewDir . '/404.php', '12345');

        $response = ErrorResponder::respond($request, 404, 'msg');
        $this->assertSame('12345', (string) $response->getBody());
    }

    /**
     * Dödar mutanter relaterade till buffert-hantering och tillstånd.
     */
    public function testRespondBufferAndStateIntegrity(): void
    {
        $request = $this->makeRequest('/test');
        $levelBefore = ob_get_level();

        // 1. Döda UnwrapRtrim (Rad 41): Provocera fram misslyckande med punkt-suffix
        $oldPath = ErrorResponder::$viewPath;
        // Vi lägger till /./ i slutet. Utan rtrim blir sökvägen till 404.php korrupt för is_file()
        ErrorResponder::$viewPath = $this->testViewDir . DIRECTORY_SEPARATOR . '.' . DIRECTORY_SEPARATOR;
        file_put_contents($this->testViewDir . '/404.php', 'RTRIM_MATCH');

        $response = ErrorResponder::respond($request, 404, 'msg');
        $this->assertSame('RTRIM_MATCH', (string) $response->getBody(), 'rtrim() muterades bort - filen hittades inte!');
        ErrorResponder::$viewPath = $oldPath;

        // 2. Döda CastString genom att simulera en stängd buffert
        file_put_contents($this->testViewDir . '/404.php', '<?php /* Ingen output */ ?>');
        $response = ErrorResponder::respond($request, 404, 'EmptyTest');
        $this->assertSame('<h1>404 | EmptyTest</h1>', (string) $response->getBody());

        // 3. Döda UnwrapTrim (Rad 68): En vy med bara whitespace MÅSTE ge fallback-H1
        file_put_contents($this->testViewDir . '/404.php', '   ');
        $response = ErrorResponder::respond($request, 404, 'WhitespaceTest');
        $this->assertSame('<h1>404 | WhitespaceTest</h1>', (string) $response->getBody(), 'Filer med bara blanksteg ska trigga fallback via trim().');

        // 4. Verifiera krasch-hantering och buffert-nivå
        file_put_contents($this->testViewDir . '/404.php', '<?php throw new \Exception("Fail"); ?>');
        $response = ErrorResponder::respond($request, 404, 'Crash');
        $this->assertSame('<h1>404 | Crash</h1>', (string) $response->getBody());
        $this->assertSame($levelBefore, ob_get_level(), 'Buffert läckte vid krasch!');
    }

    /**
     * Dödar LogicalOr (rad 67) genom att testa en tom fil.
     */
    public function testRespondHandlesEmptyFileWithFallback(): void
    {
        $request = $this->makeRequest('/test');
        file_put_contents($this->testViewDir . '/404.php', ''); // Helt tom fil

        $response = ErrorResponder::respond($request, 404, 'Empty');
        $this->assertSame('<h1>404 | Empty</h1>', (string) $response->getBody(), 'Tomma filer ska trigga fallback-H1.');
    }
}
