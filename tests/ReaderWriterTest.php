<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\File\Reader;
use Radix\File\Writer;
use RuntimeException;
use SimpleXMLElement;

final class ReaderWriterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'radix_file_' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR;
        mkdir($this->tmpDir, 0o775, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    public function testCsvStreamCreatesMissingParentDirectories(): void
    {
        $nestedDir = $this->tmpDir . 'stream' . DIRECTORY_SEPARATOR . 'deep' . DIRECTORY_SEPARATOR . 'sub';
        $path = $nestedDir . DIRECTORY_SEPARATOR . 'data.csv';

        $this->assertDirectoryDoesNotExist($nestedDir);

        Writer::csvStream($path, function (callable $write): void {
            $write([1, 'Alice']);
        }, ['id', 'name'], ',');

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($path);
    }

    public function testCsvStreamNormalizesNonScalarValuesToJsonInFile(): void
    {
        $path = $this->tmpDir . 'stream_nested.csv';

        Writer::csvStream($path, function (callable $write): void {
            $write([1, ['x' => 1, 'y' => 2]]);
        }, ['id', 'meta'], ',');

        $raw = (string) file_get_contents($path);

        $this->assertStringContainsString(
            '"{""x"":1,""y"":2}"',
            $raw,
            'Icke-skalära värden ska serialiseras till JSON och skrivas som citerad sträng i CSV (även i csvStream).'
        );

        $this->assertStringNotContainsString(
            'Array',
            $raw,
            'PHP:s standard "Array"-sträng får inte skrivas ut för icke-skalära värden (csvStream).'
        );
    }

    public function testCsvStreamWithUtf8TargetEncodingDoesNotAlterBytes(): void
    {
        $path = $this->tmpDir . 'stream_utf8_target_bytes.csv';

        // Sträng med ogiltig UTF-8-byte i början
        $invalid = "\x80" . "foo";

        // targetEncoding = 'UTF-8' ska *inte* trigga konverteringen (villkoret ska vara false)
        Writer::csvStream(
            $path,
            function (callable $write) use ($invalid): void {
                $write([1, $invalid]);
            },
            ['id', 'val'],
            ',',
            'UTF-8'
        );

        $raw = (string) file_get_contents($path);

        // Om mutanten vänder villkoret så konvertering körs, kan byten försvinna/ändras.
        $this->assertStringContainsString($invalid, $raw, 'Bytesen ska inte ha ändrats när targetEncoding är UTF-8 (csvStream).');
    }

    public function testJsonReadWrite(): void
    {
        $path = $this->tmpDir . 'data.json';
        $data = ['a' => 1, 'b' => ['x' => 'y'], 'utf' => 'ÅÄÖ'];
        Writer::json($path, $data, pretty: true);
        $this->assertFileExists($path);

        $read = Reader::json($path, assoc: true);
        $this->assertSame($data, $read);
    }

    public function testCsvDelimiterAutodetectRespectsTenLineLimit(): void
    {
        $path = $this->tmpDir . 'limit_10.csv';

        $lines = [];

        // Header + 9 rader med exakt EN ';' och inga kommatecken
        $lines[] = "id;n\n";
        for ($i = 1; $i <= 9; $i++) {
            $lines[] = $i . ';A' . $i . "\n";
        }

        // Rad 11 från filens början: MASSOR av kommatecken, men ingen ';'
        // (rad-ordning: 1 header + 9 semikolonrader = 10 rader, sedan denna = rad 11)
        $lines[] = str_repeat('x,', 200) . "end\n";

        file_put_contents($path, implode('', $lines));

        $rows = Reader::csv($path, delimiter: null, hasHeader: true);

        // Om detectDelimiter läser fler än 10 rader (mutanten <=),
        // kommer ',' vinna och headern mappas fel → dessa asserter faller.
        $this->assertGreaterThanOrEqual(2, \count($rows));

        $this->assertSame(['id' => 1, 'n' => 'A1'], $rows[0]);
        $this->assertSame(['id' => 2, 'n' => 'A2'], $rows[1]);
    }

    public function testCsvCreatesMissingParentDirectories(): void
    {
        $nestedDir = $this->tmpDir . 'deep' . DIRECTORY_SEPARATOR . 'sub';
        $path = $nestedDir . DIRECTORY_SEPARATOR . 'data.csv';

        $this->assertDirectoryDoesNotExist($nestedDir);

        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        // Korrekt implementation (med ensureParentDir) ska skapa katalogerna
        // och skriva filen utan undantag.
        Writer::csv($path, $rows, headers: ['id', 'name'], delimiter: ',');

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($path);

        // Dubbelkolla att innehållet går att läsa tillbaka
        $read = Reader::csv($path, delimiter: ',', hasHeader: true);
        $this->assertSame($rows, $read);
    }

    public function testCsvNormalizesNonScalarValuesToJsonInFile(): void
    {
        $path = $this->tmpDir . 'nested_raw.csv';

        $rows = [
            ['id' => 1, 'meta' => ['x' => 1, 'y' => 2]],
        ];

        Writer::csv($path, $rows, headers: ['id', 'meta'], delimiter: ',');

        $raw = (string) file_get_contents($path);

        // fputcsv citerar fältet och escapear dubbla citationstecken, så
        // vi förväntar oss exakt den här formen.
        $this->assertStringContainsString(
            '"{""x"":1,""y"":2}"',
            $raw,
            'Icke-skalära värden ska serialiseras till JSON och skrivas som citerad sträng i CSV.'
        );

        // Och vi vill uttryckligen INTE se PHPs default-stringifiering av array
        $this->assertStringNotContainsString(
            'Array',
            $raw,
            'PHP:s standard "Array"-sträng får inte skrivas ut för icke-skalära värden.'
        );
    }

    public function testCsvWithUtf8TargetEncodingDoesNotAlterBytes(): void
    {
        $path = $this->tmpDir . 'utf8_target_bytes.csv';

        // Sträng med ogiltig UTF-8-byte i början
        $invalid = "\x80" . "foo";
        $rows = [
            ['id' => 1, 'val' => $invalid],
        ];

        // targetEncoding = 'UTF-8' ska *inte* trigga konverteringen
        Writer::csv($path, $rows, headers: ['id', 'val'], delimiter: ',', targetEncoding: 'UTF-8');

        $raw = (string) file_get_contents($path);

        // Säkerställ att den ogiltiga byte-sekvensen finns kvar i filen.
        // Om mutanten vänder villkoret så iconv körs, försvinner eller ändras den här byten.
        $this->assertStringContainsString($invalid, $raw, 'Bytesen ska inte ha ändrats när targetEncoding är UTF-8.');
    }

    public function testTextStreamRejectsNonPositiveChunkSize(): void
    {
        $path = $this->tmpDir . 'dummy.txt';
        file_put_contents($path, 'hello');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chunkSize must be a positive integer');

        Reader::textStream($path, function (string $chunk): void {
            // ska aldrig nå hit
        }, 0);
    }

    public function testCsvReadTrimsCellWhitespace(): void
    {
        $path = $this->tmpDir . 'trim.csv';

        // Skriv manuellt en enkel CSV med extra mellanslag runt värden
        $content = "id, name , city \n"
            . "1,  Alice  ,  Stockholm  \n"
            . "2,  Bob  ,  Göteborg  \n";

        file_put_contents($path, $content);

        $rows = Reader::csv($path, delimiter: ',', hasHeader: true);

        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice', 'city' => 'Stockholm'],
                ['id' => 2, 'name' => 'Bob', 'city' => 'Göteborg'],
            ],
            $rows
        );
    }

    public function testXmlWriteAndReadAssocWithMultipleTopLevelKeys(): void
    {
        $path = $this->tmpDir . 'data-multi.xml';
        $data = [
            'user' => [
                'id' => 1,
                'name' => 'Anna',
            ],
            'meta' => [
                'version' => '1.0',
                'env' => 'test',
            ],
        ];

        Writer::xml($path, $data, rootName: 'root');

        $arr = Reader::xml($path, assoc: true);

        $this->assertSame(
            [
                'user' => [
                    'id' => '1',
                    'name' => 'Anna',
                ],
                'meta' => [
                    'version' => '1.0',
                    'env' => 'test',
                ],
            ],
            $arr
        );
    }

    public function testCsvDelimiterAutodetectWithManyLines(): void
    {
        $path = $this->tmpDir . 'multi_lines.csv';

        // Bygg en fil där:
        // - Första 9 data-raderna använder ';' (korrekt delimiter)
        // - Rad 10 har massor av ',' (för att trigga Assignment-mutanten)
        // - Raderna efteråt har ännu fler ',' (för att trigga lines--‑mutanten)
        $lines = [];

        // Header + 9 rader med ';'
        $lines[] = "id;n\n";
        for ($i = 1; $i <= 9; $i++) {
            $lines[] = $i . ';A' . $i . "\n";
        }

        // Rad 10: många kommatecken, men fortfarande semikolon som riktig separator
        $lines[] = "10;A10,extra,commas,here\n";

        // Massor av rader med kommatecken efter de 10 första raderna
        for ($i = 11; $i <= 40; $i++) {
            $lines[] = "x{$i},y{$i},z{$i}\n";
        }

        file_put_contents($path, implode('', $lines));

        $rows = Reader::csv($path, delimiter: null, hasHeader: true);

        // Kontrollera i alla fall de första få raderna efter headern.
        // Med korrekt autodetektering (';') ska de mappas snyggt till ['id' => ..., 'n' => ...].
        // Om detectDelimiter väljer ',' (mutanter 43 eller 44) blir headern "id;n"
        // och raderna får felaktiga nycklar/värden, så dessa asserter failar.
        $this->assertGreaterThanOrEqual(3, count($rows));

        $this->assertSame(
            ['id' => 1, 'n' => 'A1'],
            $rows[0],
        );
        $this->assertSame(
            ['id' => 2, 'n' => 'A2'],
            $rows[1],
        );
        $this->assertSame(
            ['id' => 9, 'n' => 'A9'],
            $rows[8],
        );
    }

    public function testCsvReadWriteWithHeaders(): void
    {
        $path = $this->tmpDir . 'data.csv';
        $rows = [
            ['id' => 1, 'name' => 'Alice', 'city' => 'Stockholm'],
            ['id' => 2, 'name' => 'Bob', 'city' => 'Göteborg'],
        ];
        Writer::csv($path, $rows, headers: null, delimiter: ',');
        $this->assertFileExists($path);

        $read = Reader::csv($path, delimiter: ',', hasHeader: true);
        $this->assertSame($rows, $read);
    }

    public function testTsvStreamReadWrite(): void
    {
        $path = $this->tmpDir . 'data.tsv';
        $headers = ['id', 'val'];

        // Låt Writer skriva header-raden via $headers-argumentet
        Writer::csvStream($path, function (callable $write): void {
            for ($i = 1; $i <= 3; $i++) {
                $write([$i, "v{$i}"]);
            }
        }, $headers, "\t");

        $collected = [];
        Reader::csvStream($path, function (array $row) use (&$collected): void {
            $collected[] = $row;
        }, "\t", hasHeader: true);

        $this->assertSame(
            [
                ['id' => 1, 'val' => 'v1'],
                ['id' => 2, 'val' => 'v2'],
                ['id' => 3, 'val' => 'v3'],
            ],
            $collected
        );
    }

    public function testCsvUsesEnsureParentDir(): void
    {
        // Skapa en FIL där katalogen borde vara
        $fileAsDir = $this->tmpDir . 'foo';
        file_put_contents($fileAsDir, 'x');

        $path = $fileAsDir . DIRECTORY_SEPARATOR . 'data.csv';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kunde inte skapa katalog: ' . $fileAsDir);

        Writer::csv($path, [
            ['id' => 1, 'name' => 'Alice'],
        ]);
    }

    public function testCsvSerializesNestedArraysToJsonStrings(): void
    {
        $path = $this->tmpDir . 'nested.csv';

        $rows = [
            ['id' => 1, 'meta' => ['x' => 1, 'y' => 2]],
        ];

        Writer::csv($path, $rows, headers: null, delimiter: ',');

        $read = Reader::csv($path, delimiter: ',', hasHeader: true);

        $this->assertSame(
            [
                ['id' => 1, 'meta' => '{"x":1,"y":2}'],
            ],
            $read
        );
    }

    public function testCsvStreamSkipsEmptyLinesAndContinues(): void
    {
        $path = $this->tmpDir . 'with_empty_line.csv';

        // Header + rad 1 + TOM rad + rad 2
        $content = "id,name\n"
            . "1,Alice\n"
            . "\n"
            . "2,Bob\n";

        file_put_contents($path, $content);

        $rows = Reader::csv($path, delimiter: ',', hasHeader: true);

        $this->assertSame(
            [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
            $rows
        );
    }

    public function testCsvDelimiterAutodetect(): void
    {
        $path = $this->tmpDir . 'semi.csv';
        $rows = [
            ['id' => 1, 'n' => 'A'],
            ['id' => 2, 'n' => 'B'],
        ];
        Writer::csv($path, $rows, headers: ['id', 'n'], delimiter: ';');
        $read = Reader::csv($path, delimiter: null, hasHeader: true);
        $this->assertSame(
            [
                ['id' => 1, 'n' => 'A'],
                ['id' => 2, 'n' => 'B'],
            ],
            $read
        );
    }

    public function testCsvDelimiterAutodetectIgnoresNoiseAfterFirstTenLines(): void
    {
        $path = $this->tmpDir . 'semi_noise_after_10.csv';

        $lines = [];

        // Header + 10 rader med ';' som korrekt delimiter
        $lines[] = "id;n\n";
        for ($i = 1; $i <= 10; $i++) {
            $lines[] = $i . ';A' . $i . "\n";
        }

        // Rad 11: massor av kommatecken (ska INTE påverka korrekt implementation)
        $lines[] = "garbage,with,many,commas,that,should,not,affect,delimiter\n";

        // Några fler rader med kommatecken bara för att förstärka "bruset"
        for ($i = 12; $i <= 30; $i++) {
            $lines[] = "x{$i},y{$i},z{$i}\n";
        }

        file_put_contents($path, implode('', $lines));

        $rows = Reader::csv($path, delimiter: null, hasHeader: true);

        // Om detectDelimiter läser fler än 10 rader (mutanten lines <= 10)
        // riskerar den att välja ',' som delimiter, och då blir mappningen fel.
        $this->assertGreaterThanOrEqual(3, count($rows));

        $this->assertSame(['id' => 1, 'n' => 'A1'], $rows[0]);
        $this->assertSame(['id' => 2, 'n' => 'A2'], $rows[1]);
        $this->assertSame(['id' => 10, 'n' => 'A10'], $rows[9]);
    }

    public function testNdjsonStreamReadWrite(): void
    {
        $path = $this->tmpDir . 'data.ndjson';
        $items = [
            ['i' => 1, 't' => 'a'],
            ['i' => 2, 't' => 'b'],
        ];
        Writer::ndjsonStream($path, function (callable $write) use ($items): void {
            foreach ($items as $it) {
                $write($it);
            }
        });

        $collected = [];
        Reader::ndjsonStream($path, function ($item) use (&$collected): void {
            $collected[] = $item;
        }, assoc: true);

        $this->assertSame($items, $collected);
    }

    public function testEncodingConversionIsoToUtf8AndBack(): void
    {
        $path = $this->tmpDir . 'latin1.tsv';
        $rows = [
            ['id', 'name'],
            [1, 'Åsa'],
            [2, 'Björn'],
        ];
        // Skriv som ISO-8859-1 TSV
        Writer::csv($path, [ ['id','name'], ['1','Åsa'], ['2','Björn'] ], delimiter: "\t", targetEncoding: 'ISO-8859-1');

        // Läs som UTF-8 med explicit källa, men behåll strängar
        $read = Reader::csv($path, delimiter: "\t", hasHeader: true, encoding: 'ISO-8859-1', castNumeric: false);
        $this->assertSame(
            [
                ['id' => '1', 'name' => 'Åsa'],
                ['id' => '2', 'name' => 'Björn'],
            ],
            $read
        );
    }

    public function testTargetEncodingUtf8DoesNotStripInvalidBytes(): void
    {
        $path = $this->tmpDir . 'utf8_invalid.csv';

        // Bygg en sträng med ogiltiga UTF-8-byte (t.ex. 0x80) som ska behållas
        $invalid = "\x80" . "foo";
        $rows = [
            ['id' => 1, 'val' => $invalid],
        ];

        // Skriv med targetEncoding = 'UTF-8' – korrekt implementation ska INTE
        // gå igenom konverteringsloopen och därmed inte köra iconv på $invalid.
        Writer::csv($path, $rows, headers: ['id', 'val'], delimiter: ',', targetEncoding: 'UTF-8');

        $raw = (string) file_get_contents($path);
        // Säkerställ att den ogiltiga byten finns kvar i filen
        $this->assertStringContainsString($invalid, $raw, 'Ogiltiga UTF-8-byte ska inte ha filtrerats bort när targetEncoding är UTF-8.');
    }

    public function testTextStreamAndWrite(): void
    {
        $path = $this->tmpDir . 'big.txt';
        $content = str_repeat("radix\n", 1000);
        Writer::text($path, $content);

        $buf = '';
        Reader::textStream($path, function (string $chunk) use (&$buf): void {
            $buf .= $chunk;
        }, 4096);

        $this->assertSame($content, $buf);
    }

    public function testTextWriterCreatesMissingParentDirectories(): void
    {
        $nestedDir = $this->tmpDir . 'nested' . DIRECTORY_SEPARATOR . 'sub';
        $path = $nestedDir . DIRECTORY_SEPARATOR . 'file.txt';

        // För säkerhets skull: katalogen ska inte finnas innan
        $this->assertDirectoryDoesNotExist($nestedDir);

        // Korrekt implementation ska skapa katalogerna via ensureParentDir()
        // och lyckas skriva filen utan undantag.
        Writer::text($path, 'hello world');

        $this->assertDirectoryExists($nestedDir);
        $this->assertFileExists($path);
        $this->assertSame('hello world', file_get_contents($path));
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($p)) {
                $this->deleteDirectory($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }

    public function testXmlWriteAndReadAssoc(): void
    {
        $path = $this->tmpDir . 'data.xml';
        $data = [
            'user' => [
                'id' => 1,
                'name' => 'Anna',
                'active' => true,
            ],
        ];

        // Skriv XML
        Writer::xml($path, $data, rootName: 'root');
        $this->assertFileExists($path);

        // Läs som assoc-array
        $arr = Reader::xml($path, assoc: true);
        $this->assertSame(
            ['user' => ['id' => '1', 'name' => 'Anna', 'active' => 'true']],
            $arr
        );
    }

    public function testXmlWriteAndReadSimpleXml(): void
    {
        $path = $this->tmpDir . 'data2.xml';
        $data = [
            'items' => [
                'item' => [
                    ['id' => 1, 'label' => 'A'],
                    ['id' => 2, 'label' => 'B'],
                ],
            ],
        ];

        Writer::xml($path, $data, rootName: 'root');
        $xml = Reader::xml($path, assoc: false);

        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertSame('1', (string) $xml->items->item->item[0]->id);
        $this->assertSame('B', (string) $xml->items->item->item[1]->label);
    }
}
