# docs/FILES.md

← [`Tillbaka till index`](INDEX.md)

# Filhantering (Radix App)

Radix App använder filstöd från **Radix Framework** via:

```php
Radix\File\Reader
Radix\File\Writer
Radix\File\Upload
Radix\File\Image
```

Den här sidan fokuserar på läsning och skrivning av datafiler med `Reader` och `Writer`.

För uppladdningar och bilder, se:

- [`IMAGES.md`](IMAGES.md)

---

## Översikt

`Reader` och `Writer` är praktiska för:

- import
- export
- batch-jobb
- integrationer
- JSON-filer
- CSV-filer
- XML-filer
- NDJSON
- textfiler
- streaming av stora filer

---

## Reader

`Radix\File\Reader` erbjuder statiska metoder för att läsa filer.

Vanliga format:

```text
text
json
csv
xml
ndjson
```

Exempel:

```php
use Radix\File\Reader;

$data = Reader::json(ROOT_PATH . '/storage/data.json');
```

---

## Writer

`Radix\File\Writer` erbjuder statiska metoder för att skriva filer.

Vanliga format:

```text
text
json
csv
xml
ndjson
```

Exempel:

```php
use Radix\File\Writer;

Writer::json(ROOT_PATH . '/storage/output.json', [
    'status' => 'ok',
]);
```

---

## Sökvägar

Använd tydliga sökvägar.

Exempel:

```php
$path = ROOT_PATH . '/storage/export/users.json';
```

För publika filer:

```php
$path = ROOT_PATH . '/public/uploads/export.csv';
```

För privata filer:

```php
$path = ROOT_PATH . '/storage/private/export.csv';
```

Rekommendation:

- publika filer kan ligga under `public/`
- privata filer bör ligga utanför `public/`
- använd inte användarinput direkt som filpath

---

## Läsa text

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

$text = Reader::text(ROOT_PATH . '/storage/example.txt');
```

Med encoding-konvertering till UTF-8:

```php
$text = Reader::text(ROOT_PATH . '/storage/legacy.txt', 'ISO-8859-1');
```

---

## Skriva text

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

Writer::text(ROOT_PATH . '/storage/logs/test.log', 'Händelse registrerad.');
```

Skriv med target encoding:

```php
Writer::text(ROOT_PATH . '/storage/export.txt', $content, 'ISO-8859-1');
```

---

## Läsa JSON

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

$data = Reader::json(ROOT_PATH . '/storage/data.json');
```

Som assoc array:

```php
$data = Reader::json(ROOT_PATH . '/storage/data.json', assoc: true);
```

Som object:

```php
$data = Reader::json(ROOT_PATH . '/storage/data.json', assoc: false);
```

---

## Skriva JSON

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

Writer::json(ROOT_PATH . '/storage/output.json', [
    'status' => 'ok',
    'count' => 5,
]);
```

Pretty print är normalt aktiverat som default.

Utan pretty print:

```php
Writer::json(
    ROOT_PATH . '/storage/output.json',
    ['status' => 'ok'],
    pretty: false
);
```

Med extra JSON flags:

```php
Writer::json(
    ROOT_PATH . '/storage/output.json',
    ['name' => 'Mats'],
    flags: JSON_UNESCAPED_UNICODE
);
```

---

## Läsa CSV

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

$rows = Reader::csv(
    ROOT_PATH . '/storage/users.csv',
    delimiter: ',',
    hasHeader: true
);
```

Med semikolon:

```php
$rows = Reader::csv(
    ROOT_PATH . '/storage/users.csv',
    delimiter: ';',
    hasHeader: true
);
```

Om `hasHeader` är true returneras rader normalt som assoc-arrayer.

Exempel:

```php
[
    [
        'id' => '1',
        'email' => 'test@example.com',
    ],
]
```

---

## Skriva CSV

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

$rows = [
    ['id' => '1', 'email' => 'test@example.com'],
    ['id' => '2', 'email' => 'demo@example.com'],
];

Writer::csv(
    ROOT_PATH . '/storage/users.csv',
    $rows,
    headers: ['id', 'email']
);
```

Med delimiter:

```php
Writer::csv(
    ROOT_PATH . '/storage/users.csv',
    $rows,
    delimiter: ';',
    headers: ['id', 'email']
);
```

---

## CSV till JSON

Reader kan läsa CSV och skriva eller returnera JSON-format beroende på metodens API.

Exempel:

```php
use Radix\File\Reader;

$json = Reader::csvToJson(
    ROOT_PATH . '/storage/users.csv',
    delimiter: ',',
    hasHeader: true
);
```

---

## JSON till CSV

Writer kan konvertera JSON till CSV.

Exempel:

```php
use Radix\File\Writer;

Writer::jsonToCsv(
    ROOT_PATH . '/storage/users.json',
    ROOT_PATH . '/storage/users.csv'
);
```

---

## Läsa XML

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

$config = Reader::xml(
    ROOT_PATH . '/storage/config.xml',
    assoc: true
);
```

Som `SimpleXMLElement`:

```php
$xml = Reader::xml(
    ROOT_PATH . '/storage/config.xml',
    assoc: false
);
```

Med encoding:

```php
$config = Reader::xml(
    ROOT_PATH . '/storage/config.xml',
    assoc: true,
    encoding: 'ISO-8859-1'
);
```

---

## Skriva XML

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

Writer::xml(
    ROOT_PATH . '/storage/output.xml',
    [
        'status' => 'ok',
        'count' => 5,
    ],
    rootName: 'response'
);
```

---

## NDJSON

NDJSON betyder newline-delimited JSON.

Det är användbart för stora exporter/importer där varje rad är ett eget JSON-objekt.

Exempel:

```text
{"id":1,"email":"a@example.com"}
{"id":2,"email":"b@example.com"}
{"id":3,"email":"c@example.com"}
```

---

## Läsa NDJSON som stream

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

Reader::ndjsonStream(
    ROOT_PATH . '/storage/events.ndjson',
    function (array $item): void {
        // Hantera en rad i taget.
    },
    assoc: true
);
```

---

## Skriva NDJSON som stream

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

Writer::ndjsonStream(
    ROOT_PATH . '/storage/export.ndjson',
    function (callable $write): void {
        $write([
            'id' => 1,
            'event' => 'created',
        ]);

        $write([
            'id' => 2,
            'event' => 'updated',
        ]);
    }
);
```

---

## Streaming

Streaming används när filer är stora och du inte vill läsa allt till minnet på en gång.

Radix Reader/Writer stödjer streaming för bland annat:

```text
csv
text
ndjson
```

---

## Läsa CSV som stream

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

Reader::csvStream(
    ROOT_PATH . '/storage/huge_data.csv',
    function (array $row): void {
        echo $row['email'] ?? '';
    },
    hasHeader: true
);
```

---

## Skriva CSV som stream

```php
<?php

declare(strict_types=1);

use Radix\File\Writer;

Writer::csvStream(
    ROOT_PATH . '/storage/export.csv',
    function (callable $writeRow): void {
        foreach ($this->users->lazy() as $user) {
            $writeRow([
                $user->id,
                $user->email,
            ]);
        }
    },
    headers: ['ID', 'Email']
);
```

---

## Läsa text som stream

```php
<?php

declare(strict_types=1);

use Radix\File\Reader;

Reader::textStream(
    ROOT_PATH . '/storage/big.log',
    function (string $chunk): void {
        // Hantera chunk.
    },
    chunkSize: 8192
);
```

---

## Encoding

Reader och Writer kan hantera encoding-konvertering.

Läs CP1252 och få UTF-8:

```php
$content = Reader::text(
    ROOT_PATH . '/storage/windows.txt',
    'CP1252'
);
```

Skriv UTF-8 till ISO-8859-1:

```php
Writer::text(
    ROOT_PATH . '/storage/export.txt',
    $utf8String,
    'ISO-8859-1'
);
```

Det är användbart vid integration med äldre system.

---

## Importflöde

Exempel på importflöde:

```text
ladda upp fil
validera filtyp/storlek
spara temporärt
läs med Reader
validera rader
skriv till databas
logga resultat
radera temporär fil
```

Exempel:

```php
Reader::csvStream(
    ROOT_PATH . '/storage/import/users.csv',
    function (array $row): void {
        // Validera rad.
        // Skapa eller uppdatera modell.
    },
    hasHeader: true
);
```

---

## Exportflöde

Exempel på exportflöde:

```text
hämta data från databas
skriv fil med Writer
spara i storage eller public
returnera download response eller länk
```

Exempel:

```php
Writer::csvStream(
    ROOT_PATH . '/storage/export/users.csv',
    function (callable $writeRow): void {
        foreach (\App\Models\User::orderBy('id')->lazy(1000) as $user) {
            $writeRow([
                $user->id,
                $user->email,
            ]);
        }
    },
    headers: ['ID', 'Email']
);
```

---

## Validera rader

När du importerar CSV/JSON bör du validera varje rad innan den används.

Exempel:

```php
use Radix\Support\Validator;

Reader::csvStream(
    ROOT_PATH . '/storage/import/users.csv',
    function (array $row): void {
        $validator = new Validator($row, [
            'email' => 'required|email',
            'name' => 'required|max:100',
        ]);

        if (!$validator->validate()) {
            // logga fel och hoppa över raden
            return;
        }

        // spara raden
    },
    hasHeader: true
);
```

---

## Säkerhet

### Läs inte godtyckliga paths från användare

Undvik:

```php
$path = $_GET['file'];
Reader::text($path);
```

Gör hellre:

```php
$allowed = [
    'users' => ROOT_PATH . '/storage/export/users.csv',
];

$key = (string) ($_GET['file'] ?? '');

if (!isset($allowed[$key])) {
    throw new RuntimeException('Invalid file.');
}

$content = Reader::text($allowed[$key]);
```

### Skydda privata filer

Lägg privata filer utanför `public/`:

```text
storage/private/
```

Servera dem via controller efter behörighetskontroll.

### Validera importdata

Lita inte på innehållet i filer från användare eller externa system.

### Undvik path traversal

Tillåt inte:

```text
../
..\ 
absoluta paths från användare
```

---

## Publika downloads

Om en fil ska vara publik kan den ligga under:

```text
public/uploads/
```

eller en särskild publik exportkatalog.

Spara då publik path:

```text
/uploads/exports/users.csv
```

Men tänk på:

- filen blir åtkomlig för alla som kan URL:en
- känslig data ska inte ligga publikt
- använd kortlivade eller auth-skyddade downloads för känsligt innehåll

---

## Privata downloads

För privata downloads:

```text
storage/private/
```

Controller:

```php
public function download(string $id): \Radix\Http\Response
{
    // kontrollera auth/behörighet
    // slå upp filmetadata
    // streama filen
}
```

---

## Felhantering

Reader/Writer kan kasta exceptions vid till exempel:

- fil saknas
- fil kan inte läsas
- ogiltig JSON
- ogiltig XML
- fil kan inte skrivas
- encoding-problem
- felaktiga rader

Hantera exceptions där det passar:

```php
try {
    $data = Reader::json($path);
} catch (\Throwable $e) {
    // logga och visa fel
}
```

---

## Kataloger

Writer skapar normalt kataloger vid behov.

Exempel:

```php
Writer::json(ROOT_PATH . '/storage/export/users.json', [
    'status' => 'ok',
]);
```

Om katalogen inte finns kan Writer skapa den beroende på metodens implementation.

Kontrollera filrättigheter om skrivning misslyckas.

---

## Testning

När du testar filhantering:

- använd temporära kataloger
- rensa efter test
- testa både giltiga och ogiltiga filer
- testa stora filer med streaming
- testa encoding
- testa felhantering

Kör:

```bash
composer test
```

---

## Bra praxis

- använd absoluta paths från appens root
- håll privata filer utanför `public/`
- validera all importdata
- använd streaming för stora filer
- använd genererade filnamn för uploads
- använd allowlists för downloads
- logga importfel tydligt
- rensa temporära filer
- undvik att lita på filnamn från användare

---

## Felsökning

### Fil hittas inte

Kontrollera path:

```php
var_dump($path);
```

Kontrollera att filen finns och att PHP-processen har läsrättighet.

### Kan inte skriva fil

Kontrollera:

- katalog finns
- PHP-processen har skrivrättighet
- disk är inte full
- path är korrekt

### CSV får konstiga tecken

Kontrollera encoding:

```php
$rows = Reader::csv($path, encoding: 'ISO-8859-1');
```

eller konvertera text innan import.

### JSON går inte att läsa

Kontrollera att filen innehåller giltig JSON.

### XML går inte att läsa

Kontrollera att XML är välformad.

### Minnesproblem

Använd streaming:

```php
Reader::csvStream($path, $callback, hasHeader: true);
```

eller:

```php
Reader::ndjsonStream($path, $callback);
```

---

## Relaterat

- [`IMAGES.md`](IMAGES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)
- [`SECURITY.md`](SECURITY.md)
- [`TESTING.md`](TESTING.md)
