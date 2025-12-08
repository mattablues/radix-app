<?php

declare(strict_types=1);

namespace Radix\Support {
    /**
     * Spy för filsystem-anrop från FileCache (och ev. andra Support-klasser).
     */
    class FileCacheSpy
    {
        public static bool $forceFilePutContentsFail = false;
        public static int $chmodCallCount = 0;
        public static ?string $lastChmodPath = null;
        public static ?string $lastFilePath = null;
        public static int $mkdirCallCount = 0;
        public static ?string $lastMkdirPath = null;
        public static ?int $lastMkdirPermissions = null;

        public static function reset(): void
        {
            self::$forceFilePutContentsFail = false;
            self::$chmodCallCount = 0;
            self::$lastChmodPath = null;
            self::$lastFilePath = null;
            self::$mkdirCallCount = 0;
            self::$lastMkdirPath = null;
            self::$lastMkdirPermissions = null;
        }
    }

    /**
     * Överskuggar global file_put_contents i Radix\Support-namespace.
     *
     * @param resource|null $context
     */
    function file_put_contents(string $filename, mixed $data, int $flags = 0, mixed $context = null): int|false
    {
        FileCacheSpy::$lastFilePath = $filename;
        if (FileCacheSpy::$forceFilePutContentsFail) {
            return false; // simulera skriv-fel
        }
        /** @var resource|null $context */
        return \file_put_contents($filename, $data, $flags, $context);
    }

    function chmod(string $filename, int $permissions): bool
    {
        FileCacheSpy::$chmodCallCount++;
        FileCacheSpy::$lastChmodPath = $filename;

        return \chmod($filename, $permissions);
    }

    /**
     * Överskuggar mkdir i Radix\Support för att kunna se att FileCache-konstruktorn
     * faktiskt försöker skapa katalogen när den saknas.
     *
     * @param resource|null $context
     */
    function mkdir(string $directory, int $permissions = 0o777, bool $recursive = false, mixed $context = null): bool
    {
        FileCacheSpy::$mkdirCallCount++;
        FileCacheSpy::$lastMkdirPath = $directory;
        FileCacheSpy::$lastMkdirPermissions = $permissions;

        /** @var resource|null $context */
        return \mkdir($directory, $permissions, $recursive, $context);
    }
}

namespace Radix\Tests\Support {

    use DateInterval;
    use PHPUnit\Framework\TestCase;
    use Radix\Support\FileCache;
    use Radix\Support\FileCacheSpy;

    final class FileCacheTest extends TestCase
    {
        private string $tmpDir;
        private FileCache $cache;

        protected function setUp(): void
        {
            parent::setUp();

            // Se till att ROOT_PATH pekar på projektroten (Radix/)
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', dirname(__DIR__, 2));
            }

            $this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'radix_filecache_' . bin2hex(random_bytes(4));

            @mkdir($this->tmpDir, 0o755, true);

            // Den här använder fortfarande tmpDir (som tidigare)
            $this->cache = new FileCache($this->tmpDir);

            FileCacheSpy::reset();
        }

        protected function tearDown(): void
        {
            $this->deleteDirectory($this->tmpDir);
            FileCacheSpy::reset();
            parent::tearDown();
        }

        public function testSetAndGet(): void
        {
            $this->assertNull($this->cache->get('missing'));

            $this->assertTrue($this->cache->set('key1', 'value1', 60));
            $this->assertSame('value1', $this->cache->get('key1'));
        }

        public function testGetWithDefault(): void
        {
            $this->assertSame('def', $this->cache->get('nope', 'def'));
        }

        /**
         * Dödar Coalesce-mutanten: return $payload['v'] ?? $default;
         *                        -> return $default ?? $payload['v'];
         */
        public function testGetPrefersStoredValueOverDefaultWhenKeyExists(): void
        {
            $this->assertTrue($this->cache->set('has_value', 'stored_value', 60));

            // Om coalesce-mutanten ändras kommer default-värdet "fallback" att användas istället.
            $this->assertSame('stored_value', $this->cache->get('has_value', 'fallback'));
        }

        public function testDelete(): void
        {
            $this->cache->set('k', ['a' => 1], 60);
            $this->assertNotNull($this->cache->get('k'));
            $this->assertTrue($this->cache->delete('k'));
            $this->assertNull($this->cache->get('k'));
        }

        /**
         * Dödar TrueValue-mutanten i delete():
         * is_file ? unlink : true  ->  is_file ? unlink : false
         */
        public function testDeleteOnMissingKeyReturnsTrue(): void
        {
            // Ingen fil skapad för nyckeln -> is_file == false
            $this->assertTrue($this->cache->delete('totally_missing_key'));
        }

        public function testClear(): void
        {
            $this->cache->set('a', 1, 60);
            $this->cache->set('b', 2, 60);
            $this->assertTrue($this->cache->clear());
            $this->assertNull($this->cache->get('a'));
            $this->assertNull($this->cache->get('b'));
        }

        public function testTtlExpiry(): void
        {
            $this->cache->set('short', 'x', 1);
            $this->assertSame('x', $this->cache->get('short'));

            // simulera utgången TTL
            sleep(2);
            $this->assertNull($this->cache->get('short'));
        }

        public function testSetWithDateInterval(): void
        {
            // Testa att använda DateInterval som TTL
            $ttl = new DateInterval('PT1H'); // 1 timme
            $this->assertTrue($this->cache->set('interval', 'value_interval', $ttl));
            $this->assertSame('value_interval', $this->cache->get('interval'));
        }

        public function testGetHandlesCorruptedJson(): void
        {
            // Skapa en korrupt cachefil manuellt
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'corrupt.cache';
            file_put_contents($file, '{invalid_json');

            $this->assertNull($this->cache->get('corrupt'));
        }

        public function testGetHandlesNonArrayPayload(): void
        {
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'not_array.cache';
            file_put_contents($file, '"just_a_string"');

            $this->assertNull($this->cache->get('not_array'));
        }

        public function testGetHandlesMissingExpiresKey(): void
        {
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'no_expires.cache';
            file_put_contents($file, json_encode(['v' => 'value']));

            $this->assertSame('value', $this->cache->get('no_expires'));
        }

        public function testGetIgnoresNonNumericExpiresValue(): void
        {
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'non_numeric_expires.cache';
            file_put_contents($file, json_encode([
                'v' => 'keep_me',
                'e' => 'not_numeric',
            ]));

            // Med korrekt implementation ska icke-numerisk 'e' behandlas som 0 (aldrig utgången)
            $this->assertSame('keep_me', $this->cache->get('non_numeric_expires', 'fallback'));

            // Och filen ska fortfarande finnas kvar
            $this->assertFileExists($file);
        }

        public function testGetDoesNotTreatBoundaryTimeAsExpired(): void
        {
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'boundary_expires.cache';

            $expires = time();
            file_put_contents($file, json_encode([
                'v' => 'boundary_value',
                'e' => $expires,
            ]));

            // Precis vid gränsen (time() == expires) ska värdet fortfarande anses giltigt
            $value = $this->cache->get('boundary_expires', 'fallback');

            $this->assertSame('boundary_value', $value, 'Cache-värdet ska inte anses utgånget när time() == expires.');
            $this->assertFileExists($file, 'Cachefilen ska inte tas bort vid gräns-tidpunkten.');
        }

        public function testZeroTtlDoesNotExpire(): void
        {
            $this->assertTrue($this->cache->set('zero_ttl', 'value_zero', 0));

            $this->assertSame('value_zero', $this->cache->get('zero_ttl'));

            sleep(2);

            $this->assertSame('value_zero', $this->cache->get('zero_ttl'));
        }

        public function testZeroAndNegativeTtlStoreExpiresAsZero(): void
        {
            $this->assertTrue($this->cache->set('zero_ttl_file', 'v0', 0));
            $fileZero = $this->tmpDir . DIRECTORY_SEPARATOR . 'zero_ttl_file.cache';
            $this->assertFileExists($fileZero);
            $payloadZero = json_decode((string) file_get_contents($fileZero), true);
            $this->assertIsArray($payloadZero);
            $this->assertArrayHasKey('e', $payloadZero);
            $this->assertSame(0, $payloadZero['e']);

            $this->assertTrue($this->cache->set('negative_ttl_file', 'v-5', -5));
            $fileNegative = $this->tmpDir . DIRECTORY_SEPARATOR . 'negative_ttl_file.cache';
            $this->assertFileExists($fileNegative);
            $payloadNegative = json_decode((string) file_get_contents($fileNegative), true);
            $this->assertIsArray($payloadNegative);
            $this->assertArrayHasKey('e', $payloadNegative);
            $this->assertSame(0, $payloadNegative['e']);
        }

        public function testClearReturnsFalseWhenUnlinkFailsOnSingleEntry(): void
        {
            $dir = $this->tmpDir . DIRECTORY_SEPARATOR . 'only_directory.cache';
            $this->assertTrue(@mkdir($dir));

            $this->assertFalse($this->cache->clear());
        }

        public function testClearReturnsFalseWhenFirstUnlinkFailsButSecondSucceeds(): void
        {
            $dir = $this->tmpDir . DIRECTORY_SEPARATOR . 'a.cache';
            $this->assertTrue(@mkdir($dir));

            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'b.cache';
            $this->assertNotFalse(file_put_contents($file, 'x'));

            $this->assertFalse($this->cache->clear());
            $this->assertFileDoesNotExist($file);
        }

        public function testDefaultConstructorCreatesCacheDirectory(): void
        {
            $cache = new FileCache();

            $this->assertTrue($cache->set('default_key', 'default_value', 60));
            $this->assertSame('default_value', $cache->get('default_key'));
        }

        public function testJsonEncodingDoesNotEscapeSlashesOrUnicode(): void
        {
            $value = 'http://example.com/åäö';

            $this->assertTrue($this->cache->set('json_flags', $value, 60));

            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'json_flags.cache';
            $this->assertFileExists($file);

            $raw = (string) file_get_contents($file);

            $this->assertStringNotContainsString('\/', $raw);
            $this->assertStringNotContainsString('\u00e5', strtolower($raw));
            $this->assertStringNotContainsString('\u00e4', strtolower($raw));
            $this->assertStringNotContainsString('\u00f6', strtolower($raw));
        }

        /**
         * Dödar IfNegation-mutanten i set():
         * if ($ok) { chmod(...) }  ->  if (!$ok) { chmod(...) }
         */
        public function testSetDoesNotCallChmodOnWriteFailure(): void
        {
            FileCacheSpy::reset();
            FileCacheSpy::$forceFilePutContentsFail = true;

            $result = $this->cache->set('write_fail', 'value', 60);

            $this->assertFalse($result, 'set() ska returnera false när file_put_contents misslyckas.');
            $this->assertSame(
                0,
                FileCacheSpy::$chmodCallCount,
                'chmod() ska inte anropas när skrivningen misslyckas.'
            );
        }

        /**
         * Dödar LogicalNot-mutanten i konstruktorn:
         * if (!is_dir($base)) { mkdir(...) }  ->  if (is_dir($base)) { mkdir(...) }
         *
         * Och Decrement/IncrementInteger på mkdir-rättigheterna (0o755 +/- 1).
         */
        public function testConstructorCreatesBaseDirectoryWhenMissing(): void
        {
            FileCacheSpy::reset();

            $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'radix_filecache_ctor_' . bin2hex(random_bytes(4));

            // Säkerställ att katalogen inte finns
            if (is_dir($dir)) {
                $this->deleteDirectory($dir);
            }
            $this->assertDirectoryDoesNotExist($dir);

            // När katalogen saknas ska konstruktorn anropa mkdir exakt en gång
            $cache = new FileCache($dir);

            $this->assertDirectoryExists($dir);
            $this->assertSame(1, FileCacheSpy::$mkdirCallCount, 'Konstruktorn ska anropa mkdir när bas-katalogen saknas.');
            $this->assertSame($dir, FileCacheSpy::$lastMkdirPath);
            $this->assertInstanceOf(FileCache::class, $cache);

            // På Unix kan vi också verifiera rättigheterna för att döda 0o755-mutanterna
            if (DIRECTORY_SEPARATOR === '/') {
                $this->assertSame(
                    0o755,
                    FileCacheSpy::$lastMkdirPermissions,
                    sprintf('mkdir() ska anropas med 0755, fick 0%o', FileCacheSpy::$lastMkdirPermissions ?? 0)
                );
            }
        }

        private function deleteDirectory(string $dir): void
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
                    $this->deleteDirectory($p);
                } else {
                    @unlink($p);
                }
            }
            @rmdir($dir);
        }
    }
}
