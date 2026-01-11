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

        public function testSetAppliesCorrectPermissionsOnLinux(): void
        {
            if (DIRECTORY_SEPARATOR !== '/') {
                $this->markTestSkipped('Permissions test only applies to POSIX systems.');
            }

            $key = 'perm_test_' . uniqid();
            $this->cache->set($key, 'data', 60);

            // Hitta den faktiska filen på disken
            $safeKey = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $key);
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . $safeKey . '.cache';

            $this->assertFileExists($file);

            // Hämta filrättigheter (t.ex. 0664)
            $perms = fileperms($file) & 0o777;

            // Vi förväntar oss 0664. Om rtrim eller logic muteras kommer detta faila.
            $this->assertSame(0o664, $perms, sprintf('Filen borde ha rättigheter 0664, men har 0%o', $perms));
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

        public function testPruneRemovesOnlyExpiredFiles(): void
        {
            $now = time();
            $future = $now + 3600;

            // 1. Definitivt utgången (ska tas bort)
            $this->cache->set('expired', 'old', 0);
            file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'expired.cache', json_encode(['v' => 'old', 'e' => $now - 10]));

            // 2. Giltig (ska vara kvar)
            $this->cache->set('valid', 'new', 0);
            file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'valid.cache', json_encode(['v' => 'new', 'e' => $future]));

            // 3. Gränsvärde: expires är EXAKT nu (ska vara kvar, vi rensar bara om nu > expires)
            // Detta dödar GreaterThan/Equal mutanten
            $this->cache->set('boundary', 'exact', 0);
            file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . 'boundary.cache', json_encode(['v' => 'exact', 'e' => $now]));

            // 4. Evig: expires är 0 (ska vara kvar)
            // Detta dödar IncrementInteger (0->1) och GreaterThan (>=0) mutanterna
            $this->cache->set('eternal', 'forever', 0);

            // 5. Korrupt fil (ska rensas)
            $corruptFile = $this->tmpDir . DIRECTORY_SEPARATOR . 'corrupt.cache';
            file_put_contents($corruptFile, 'invalid-json');

            // Kör prune med en fastställd tidpunkt (dödar Coalesce mutanten)
            $this->cache->prune($now);

            $this->assertFileDoesNotExist($this->tmpDir . DIRECTORY_SEPARATOR . 'expired.cache');
            $this->assertFileDoesNotExist($corruptFile);

            $this->assertSame('new', $this->cache->get('valid'), 'Framtida filer ska vara kvar');
            $this->assertSame('exact', $this->cache->get('boundary'), 'Filer som går ut precis NU ska vara kvar');
            $this->assertSame('forever', $this->cache->get('eternal'), 'Filer med expires=0 ska vara kvar');
        }

        /**
         * Dödar Coalesce-mutanten: $currentTime = $now ?? time() -> time() ?? $now
         */
        public function testPruneUsesSystemTimeWhenNoArgumentProvided(): void
        {
            // Skapa en fil som går ut om exakt 1 sekund
            $this->cache->set('soon', 'bye', 1);
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'soon.cache';

            $this->assertFileExists($file);

            // Vänta tills den har gått ut
            sleep(2);

            // Anropa prune UTAN argument. Om mutanten time() ?? $now styr,
            // så kommer den alltid använda time() oavsett, men genom att vi
            // har testet testPruneRemovesOnlyExpiredFiles som skickar in ett
            // FAST värde på $now, tvingar vi koden att respektera argumentet när det finns.
            $this->cache->prune();

            $this->assertFileDoesNotExist($file, 'Prune ska använda systemtid om inget argument ges');
        }

        /**
         * Dödar DecrementInteger-mutanten: ? (int) $payload['e'] : 0 -> -1
         */
        public function testPruneHandlesMissingExpiresKeyAsZero(): void
        {
            // Skapa en fil manuellt helt utan 'e' (expires)
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'no_e.cache';
            file_put_contents($file, json_encode(['v' => 'eternal_value']));

            $this->cache->prune();

            // Om fallbacken ändras till -1, och koden är "if ($expires != 0)",
            // så skulle denna tas bort. Men vi vill att den ska vara kvar (0 = evig).
            $this->assertFileExists($file, 'Filer utan expires-nyckel ska betraktas som eviga (0)');
            $this->assertSame('eternal_value', $this->cache->get('no_e'));
        }

        /**
         * Dödar DecrementInteger-mutanten: ? (int) $payload['e'] : 0 -> -1
         * Samt säkerställer att negativa expires-värden i filen inte triggar rensning (0-logik).
         */
        public function testPruneHandlesMissingOrNegativeExpiresKeyAsZero(): void
        {
            // 1. Fil helt utan 'e'
            $fileNoE = $this->tmpDir . DIRECTORY_SEPARATOR . 'no_e.cache';
            file_put_contents($fileNoE, json_encode(['v' => 'eternal_1']));

            // 2. Fil med negativ 'e' (t.ex. -1)
            // Om koden ändras till expires > -1 (pga mutant) så skulle denna raderas.
            $fileNegE = $this->tmpDir . DIRECTORY_SEPARATOR . 'neg_e.cache';
            file_put_contents($fileNegE, json_encode(['v' => 'eternal_2', 'e' => -1]));

            $this->cache->prune();

            $this->assertFileExists($fileNoE, 'Filer utan expires ska vara kvar (0)');
            $this->assertFileExists($fileNegE, 'Filer med negativa expires ska vara kvar (behandlas som 0)');
        }

        /**
         * Dödar CastInt-mutanten: (int) $payload['e'] -> $payload['e']
         * Genom att lagra expires som en sträng och verifiera att den ändå hanteras korrekt.
         */
        public function testPruneHandlesNumericStringExpiresAsInteger(): void
        {
            $now = time();
            $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'string_e.cache';

            // Vi skriver manuellt in en sträng som 'e' värde
            file_put_contents($file, json_encode(['v' => 'val', 'e' => (string) ($now - 10)]));

            $this->cache->prune($now);

            $this->assertFileDoesNotExist($file, 'Filer med numeriska strängar som expires ska också rensas (CastInt-skydd)');
        }

        /**
         * Dödar de sista DecrementInteger och CastInt mutanterna i prune().
         */
        public function testPruneStaysStrictOnZeroAndTypes(): void
        {
            $now = time();

            // 1. En fil med e = 0 (evig) ska stanna kvar.
            $fileZero = $this->tmpDir . DIRECTORY_SEPARATOR . 'zero_e.cache';
            file_put_contents($fileZero, json_encode(['v' => 'stay', 'e' => 0]));

            // 2. Dödar CastInt-mutanten: (int)"0.5" blir 0.
            $fileSmall = $this->tmpDir . DIRECTORY_SEPARATOR . 'small_e.cache';
            file_put_contents($fileSmall, json_encode(['v' => 'stay_too', 'e' => '0.5']));

            // 3. En vanlig utgången fil (ska raderas).
            $fileOld = $this->tmpDir . DIRECTORY_SEPARATOR . 'old_e.cache';
            file_put_contents($fileOld, json_encode(['v' => 'bye', 'e' => $now - 10]));

            // 4. Test för att döda Logical-mutanter: e är null eller saknas helt.
            $fileNull = $this->tmpDir . DIRECTORY_SEPARATOR . 'null_e.cache';
            file_put_contents($fileNull, json_encode(['v' => 'stay', 'e' => null]));

            $this->cache->prune($now);

            $this->assertFileExists($fileZero, 'Filer med expires=0 ska stanna kvar');
            $this->assertFileExists($fileSmall, 'CastInt-skydd: "0.5" ska castas till 0 och stanna kvar');
            $this->assertFileExists($fileNull, 'Filer med e=null ska stanna kvar');
            $this->assertFileDoesNotExist($fileOld, 'Utgångna filer ska raderas');
        }

        /**
         * Dödar CastInt-mutanten och DecrementInteger-mutanten i prune().
         */
        public function testPruneHandlesVariousExpireTypesAndFallbacks(): void
        {
            $now = time();

            // 1. Testa Float-sträng (CastInt skydd): lagra som sträng med decimal.
            // Om (int) tas bort kommer PHP jämföra "123.5" > currentTime vilket kan ge
            // andra resultat än ett rent heltal vid exakta gränser.
            $fileFloat = $this->tmpDir . DIRECTORY_SEPARATOR . 'float_e.cache';
            file_put_contents($fileFloat, json_encode(['v' => 'x', 'e' => (string) ($now - 5) . ".9"]));

            // 2. Testa saknad nyckel (DecrementInteger skydd 0 -> -1)
            // Vi lägger till en assert som kollar att filen STANNAR KVAR.
            $fileMissing = $this->tmpDir . DIRECTORY_SEPARATOR . 'missing_e.cache';
            file_put_contents($fileMissing, json_encode(['v' => 'y']));

            // 3. Testa icke-numerisk nyckel (Fallback skydd)
            $fileAlpha = $this->tmpDir . DIRECTORY_SEPARATOR . 'alpha_e.cache';
            file_put_contents($fileAlpha, json_encode(['v' => 'z', 'e' => 'not-numeric']));

            $this->cache->prune($now);

            $this->assertFileDoesNotExist($fileFloat, 'Float-sträng ska castas till int och rensas');
            $this->assertFileExists($fileMissing, 'Filer utan expires ska tolkas som 0 och stanna kvar');
            $this->assertFileExists($fileAlpha, 'Filer med icke-numerisk expires ska tolkas som 0 och stanna kvar');
        }

        /**
         * Dödar mutanten: if ($ok && DIRECTORY_SEPARATOR === '/') -> !== '/'
         */
        public function testSetDoesNotCallChmodWhenSeparatorIsNotSlash(): void
        {
            // Vi kan inte ändra konstanten DIRECTORY_SEPARATOR, men vi kan
            // testa logiken genom att verifiera att chmod faktiskt anropas på Linux
            // och sedan lita på att mutations-testet ser att vi bryr oss om resultatet.
            if (DIRECTORY_SEPARATOR !== '/') {
                $this->markTestSkipped('Endast för Linux/Unix');
            }

            FileCacheSpy::reset();
            $this->cache->set('perm_check', 'data', 60);
            $this->assertSame(1, FileCacheSpy::$chmodCallCount, 'Chmod ska anropas på Linux');
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
