<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Support\Logger;
use ReflectionClass;

final class TestableLogger extends Logger
{
    public function __construct() {}

    /**
     * @param array<string,mixed> $context
     */
    public function interpolatePublic(string $message, array $context): string
    {
        // Anropa den skyddade interpolate()-metoden i bas-klassen
        $ref = new ReflectionClass(Logger::class);
        $method = $ref->getMethod('interpolate');
        $method->setAccessible(true);

        /** @var string $result */
        $result = $method->invoke($this, $message, $context);

        return $result;
    }
}

final class LoggerTest extends TestCase
{
    private string $tmpRoot;
    private string $logsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/radix_logger_test_' . bin2hex(random_bytes(4));
        $this->logsDir = $this->tmpRoot . '/storage/logs';
        @mkdir($this->logsDir, 0o755, true);

        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->tmpRoot);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    public function testWritesToTodaysFile(): void
    {
        $logger = new Logger('unittest', $this->logsDir);
        $logger->info('hello {name}', ['name' => 'world']);

        $file = $this->logsDir . '/unittest-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $this->assertStringContainsString('unittest.INFO hello world', file_get_contents($file) ?: '');
    }

    public function testInterpolateReplacesPlaceholdersExactly(): void
    {
        $logger = new TestableLogger();

        $msg = $logger->interpolatePublic(
            'Hello {name}, id={id}, opt={opt}',
            [
                'name' => 'Alice',
                'id'   => 123,
                'opt'  => null,
                'ignore' => ['x' => 1],
            ]
        );

        $this->assertSame('Hello Alice, id=123, opt=', $msg);
    }

    public function testRotatesWhenMaxBytesExceeded(): void
    {
        $smallMax = 64; // tvinga rotation snabbt
        $logger = new Logger('rotate', $this->logsDir, $smallMax);

        // Skriv tills basfilen måste rotera
        for ($i = 0; $i < 50; $i++) {
            $logger->info(str_repeat('x', 40));
        }

        $base = $this->logsDir . '/rotate-' . date('Y-m-d') . '.log';
        $r1 = $base . '.1';
        $r2 = $base . '.2';

        $this->assertTrue($this->anyExisting([$base, $r1, $r2]), 'Expected at least one rotated file to exist');
        // Verifiera att inga filer överstiger gränsen med stor marginal (lite overhead för metadata)
        foreach (glob($this->logsDir . '/rotate-' . date('Y-m-d') . '.log*') ?: [] as $f) {
            $size = filesize($f) ?: 0;
            $this->assertLessThan($smallMax + 256, $size, 'Rotated file unexpectedly large: ' . $f);
        }
    }

    public function testRetentionRemovesOldFiles(): void
    {
        $retentionDays = 1;

        // Skapa en artificiellt gammal fil (2 dagar gammal) FÖRE logger initieras
        $oldFile = $this->logsDir . '/retention-' . date('Y-m-d', time() - 2 * 86400) . '.log';
        file_put_contents($oldFile, 'old');
        @touch($oldFile, time() - 2 * 86400);

        // Initiera logger EFTER att den gamla filen finns så cleanup ser den
        $logger = new Logger('retention', $this->logsDir, 1024 * 1024, $retentionDays);

        // Trigger cleanup via write
        $logger->info('trigger cleanup');

        $this->assertFileDoesNotExist($oldFile, 'Old log should be deleted by retention');
    }

    public function testRetentionDoesNotDeleteModeratelyNewFilesWithLongRetention(): void
    {
        // Retention 365 dagar, loggfil ~2 dagar gammal ska INTE raderas.
        $retentionDays = 365;

        $file = $this->logsDir . '/keep-' . date('Y-m-d', time() - 2 * 86400) . '.log';
        file_put_contents($file, 'keep');
        @touch($file, time() - 2 * 86400);

        $this->assertFileExists($file, 'Testloggfilen ska finnas innan cleanup');

        // Initiera logger EFTER att filen finns
        $logger = new Logger('keep', $this->logsDir, 1024 * 1024, $retentionDays);

        // Trigger cleanup via write
        $logger->info('trigger cleanup');

        // Med korrekt implementation (threshold = now - 365d) ska filen behållas.
        $this->assertFileExists($file, 'Loggfilen ska inte raderas vid lång retention.');
    }

    public function testRetentionOnlyRunsOncePerDayPerLoggerInstance(): void
    {
        $retentionDays = 1;

        // Skapa logger
        $logger = new Logger('daily', $this->logsDir, 1024 * 1024, $retentionDays);

        // Första körningen: gammal fil som ska rensas bort
        $old1 = $this->logsDir . '/daily-old1.log';
        file_put_contents($old1, 'old1');
        @touch($old1, time() - 2 * 86400); // 2 dagar gammal

        $this->assertFileExists($old1, 'old1 ska finnas innan första cleanup');

        $logger->info('first run');

        $this->assertFileDoesNotExist($old1, 'old1 ska raderas vid första cleanup');

        // Andra körningen samma dag: ny gammal fil ska INTE påverkas av cleanup
        $old2 = $this->logsDir . '/daily-old2.log';
        file_put_contents($old2, 'old2');
        @touch($old2, time() - 2 * 86400);

        $this->assertFileExists($old2, 'old2 ska finnas innan andra körningen');

        $logger->info('second run');

        // Med korrekt implementation (early return när lastCleanupDay === idag) ska old2 ligga kvar.
        $this->assertFileExists(
            $old2,
            'old2 ska INTE raderas av cleanup som redan körts för denna logger-instans idag'
        );
    }

    public function testContextJsonDoesNotEscapeSlashesOrUnicode(): void
    {
        $logger = new Logger('ctxjson', $this->logsDir);

        $logger->info('msg', [
            'meta' => ['url' => 'http://example.com/åäö'],
        ]);

        $file = $this->logsDir . '/ctxjson-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $content = (string) file_get_contents($file);

        // Slashes ska inte vara escapade
        $this->assertStringNotContainsString('\/', $content, 'Slashes i context JSON ska inte vara escapade');

        // Unicode ska inte vara escapad (inga \uXXXX för å/ä/ö)
        $lower = strtolower($content);
        $this->assertStringNotContainsString('\u00e5', $lower);
        $this->assertStringNotContainsString('\u00e4', $lower);
        $this->assertStringNotContainsString('\u00f6', $lower);
    }

    public function testContextToStringIncludesOnlyNonScalarValues(): void
    {
        $logger = new Logger('ctx1', $this->logsDir);

        // 'a' är skalar, 'meta' är icke-skalär
        $logger->info('msg', [
            'a'    => 1,
            'meta' => ['x' => 1],
        ]);

        $file = $this->logsDir . '/ctx1-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $content = (string) file_get_contents($file);

        // Icke-skalär 'meta' ska serialiseras till JSON
        $this->assertStringContainsString('"meta":{"x":1}', $content);

        // Skalar 'a' ska inte finnas i JSON-delen
        $this->assertStringNotContainsString('"a":1', $content);
    }

    public function testContextToStringOmitsScalarOnlyContext(): void
    {
        $logger = new Logger('ctx2', $this->logsDir);

        $logger->info('hello {name}', [
            'name' => 'world',
            'id'   => 123,
        ]);

        $file = $this->logsDir . '/ctx2-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);

        $content = (string) file_get_contents($file);

        // Skalära context-värden ska INTE dyka upp som JSON
        $this->assertStringNotContainsString('"name"', $content);
        $this->assertStringNotContainsString('"id"', $content);

        // Och vi vill absolut inte ha en tom JSON-array/string (som '[]')
        $this->assertStringNotContainsString('[]', $content);
    }

    /**
     * @param array<int, string> $files
     */
    private function anyExisting(array $files): bool
    {
        foreach ($files as $f) {
            if (is_file($f)) {
                return true;
            }
        }
        return false;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_dir($p)) {
                $this->deleteDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}
