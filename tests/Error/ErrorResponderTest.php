<?php

declare(strict_types=1);

namespace Radix\Tests\Error;

use PHPUnit\Framework\TestCase;
use Radix\Error\ErrorResponder;
use Radix\Http\Request;

final class ErrorResponderTest extends TestCase
{
    private string $testViewDir;

    protected function setUp(): void
    {
        parent::setUp();

        $projectRoot = realpath(dirname(__DIR__, 2));
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $projectRoot);
        }

        // Skapa en temporär mapp för test-vyer för att undvika att ladda de riktiga tunga vyerna
        $this->testViewDir = ROOT_PATH . '/storage/framework/testing/views/errors';
        if (!is_dir($this->testViewDir)) {
            mkdir($this->testViewDir, 0o777, true);
        }

        // Berätta för ErrorResponder att använda test-mappen
        ErrorResponder::$viewPath = $this->testViewDir;

        // Skapa extremt enkla dummy-filer som inte kräver några globala funktioner
        file_put_contents($this->testViewDir . '/404.php', '<html>Real 404 View: <?php echo $message; ?></html>');
        file_put_contents($this->testViewDir . '/500.php', '<html>Real 500 View: <?php echo $status; ?></html>');
    }

    protected function tearDown(): void
    {
        // 1. Återställ den statiska variabeln så den inte förstör för andra tester!
        ErrorResponder::$viewPath = null;

        // 2. Städa upp de temporära filerna och mappen
        $files = glob($this->testViewDir . '/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Ta även bort eventuella undermappar som skapades (t.ex. i testRespondHandlesPathWithTrailingSlash)
        if (is_dir($this->testViewDir . '/errors')) {
            $subFiles = glob($this->testViewDir . '/errors/*.php');
            if ($subFiles !== false) {
                array_map('unlink', $subFiles);
            }
            rmdir($this->testViewDir . '/errors');
        }

        if (is_dir($this->testViewDir)) {
            rmdir($this->testViewDir);
        }

        parent::tearDown();
    }

    private function makeRequest(string $uri): Request
    {
        return new Request($uri, 'GET', [], [], [], [], []);
    }

    public function testRespondForApiRequestReturnsJsonResponse(): void
    {
        $request = $this->makeRequest('/api/v1/test');
        $status = 400;
        $message = 'Bad Request';
        $jsonExtra = ['foo' => 'bar'];

        $response = ErrorResponder::respond($request, $status, $message, $jsonExtra);

        $body = (string) $response->getBody();
        $this->assertNotSame('{"error":"Unexpected error"}', $body);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);
        $this->assertSame('bar', $decoded['foo'] ?? null);
        $this->assertSame($message, $decoded['error'] ?? null);
    }

    public function testRespondDoesNotTreatNonRootedApiPathAsApi(): void
    {
        $request = $this->makeRequest('/foo/api/v1/test');
        $response = ErrorResponder::respond($request, 404, 'Not found');
        $this->assertSame('text/html; charset=UTF-8', $response->headers()['Content-Type'] ?? null);
    }

    public function testRespondWebUsesRealViewFile(): void
    {
        $request = $this->makeRequest('/test');
        $status = 404;
        $message = 'Unique-Msg-' . uniqid();

        /**
         * Nu kräver vi de nya variabelnamnen. Om extract() tas bort eller om
         * en nyckel saknas i $data, kommer isset() vara false trots att
         * $status och $message finns som argument i respond().
         */
        $phpCode = '<?php 
            if (!isset($errorStatus) || !isset($errorMessage)) { 
                throw new \RuntimeException("EXTRACT_FAILED"); 
            }
            echo "VERIFIED_VIEW:" . $errorStatus . ":" . $errorMessage; 
        ?>';
        file_put_contents($this->testViewDir . '/404.php', $phpCode);

        $response = ErrorResponder::respond($request, $status, $message);
        $body = (string) $response->getBody();

        $this->assertStringContainsString("VERIFIED_VIEW:404:" . $message, $body, 'Mutant dödad: extract() eller data-nycklar saknas');
        $this->assertStringNotContainsString('<h1>', $body);
    }

    /**
     * Dödar [M] LogicalAnd genom att testa exakt vad som händer när en fil är helt tom.
     */
    public function testRespondHandlesEmptyViewFile(): void
    {
        $request = $this->makeRequest('/test');
        $status = 404;
        $message = 'Empty-Test';

        // Skapa en helt tom fil
        file_put_contents($this->testViewDir . '/404.php', '');

        $response = ErrorResponder::respond($request, $status, $message);
        $body = (string) $response->getBody();

        // Om LogicalAnd ändras från && till ||, kommer den tro att en tom sträng är giltig
        // och returnera en tom body istället för fallback-H1.
        $this->assertSame("<h1>{$status} | {$message}</h1>", $body, 'Mutant dödad: Tomma vyfiler ska resultera i fallback-H1');
    }

    public function testRespondWebFallsBackTo500View(): void
    {
        $request = $this->makeRequest('/test');
        $status = 599;

        $response = ErrorResponder::respond($request, $status, 'System Error');
        $body = (string) $response->getBody();

        $this->assertStringContainsString('Real 500 View', $body);
        $this->assertStringContainsString((string) $status, $body);
    }

    /**
     * Detta test dödar mutanter i catch-blocket och LogicalAnd genom att tvinga fram en krasch.
     */
    public function testRespondHandlesCrashingView(): void
    {
        $request = $this->makeRequest('/test');
        // Skapa en fil som garanterat kraschar (anropar funktion som inte finns)
        file_put_contents($this->testViewDir . '/404.php', '<?php undefined_function_krasch(); ?>');

        $status = 404;
        $message = 'Krasch-test';
        $response = ErrorResponder::respond($request, $status, $message);
        $body = (string) $response->getBody();

        // 1. Dödar [M] MethodCallRemoval [setBody] i catch:
        // Vi verifierar att bodyn INTE är tom trots kraschen.
        $this->assertNotEmpty($body);

        // 2. Dödar [M] LogicalAnd [&& -> ||]:
        // Verifiera att vi fick den exakta fallback-strängen vid krasch.
        $this->assertSame("<h1>{$status} | {$message}</h1>", $body, 'Mutant dödad: Felaktig fallback-logik vid krasch');

    }

    public function testRespondHandlesPathWithTrailingSlash(): void
    {
        $request = $this->makeRequest('/test');

        // 1. Testa rtrim för $errorFile (404.php) med framåtlutade snedstreck
        ErrorResponder::$viewPath = $this->testViewDir . '/';
        file_put_contents($this->testViewDir . '/404.php', 'ERR_FILE_OK');
        $response = ErrorResponder::respond($request, 404, 'msg');
        $this->assertSame('ERR_FILE_OK', (string) $response->getBody(), 'Mutant dödad: rtrim behövs för $errorFile');

        // 2. Testa rtrim för $fallback (500.php) med bakåtlutade snedstreck (Windows-stil)
        ErrorResponder::$viewPath = $this->testViewDir . '\\';
        file_put_contents($this->testViewDir . '/500.php', 'FALLBACK_OK');
        $response = ErrorResponder::respond($request, 503, 'msg');
        $this->assertSame('FALLBACK_OK', (string) $response->getBody(), 'Mutant dödad: rtrim behövs för $fallback');

        // 3. För att döda mutanten helt när viewPath är null, testar vi en status där rtrim är avgörande
        // genom att skicka in en baspath som garanterat har snedstreck.
        ErrorResponder::$viewPath = $this->testViewDir . '/errors///';
        if (!is_dir($this->testViewDir . '/errors')) {
            mkdir($this->testViewDir . '/errors', 0o777, true);
        }
        file_put_contents($this->testViewDir . '/errors/404.php', 'DEEP_OK');
        $response = ErrorResponder::respond($request, 404, 'msg');
        $this->assertSame('DEEP_OK', (string) $response->getBody(), 'Mutant dödad: rtrim fixar djupa snedstreck');
    }
}
