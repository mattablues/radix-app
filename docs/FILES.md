# docs/FILES.md

← [`Tillbaka till index`](INDEX.md)

# Filhantering (Reader & Writer) (Radix App)

Radix tillhandahåller ett API för att läsa och skriva filer i flera format (JSON, CSV, XML, text) via:

- `Radix\File\Reader`
- `Radix\File\Writer`

Det här är praktiskt för import/export, integrationer och “batch-jobb”.

---

## Läsa filer (Reader)

`Reader` erbjuder statiska metoder för att läsa in data till PHP-strukturer.

### JSON & text

```php
<?php

use Radix\File\Reader;

// Läs JSON
$data = Reader::json('data.json');

// Läs råtext och konvertera till UTF-8 vid behov
$text = Reader::text('legacy.txt', 'ISO-8859-1');
```

### CSV & XML

```php
<?php

use Radix\File\Reader;

// CSV med headers (assoc per rad)
$rows = Reader::csv('users.csv', delimiter: ';', hasHeader: true);

// XML till array
$config = Reader::xml('config.xml', assoc: true);
```

### Streaming (för stora filer)

För stora filer kan du använda streaming för att hålla minnesanvändningen låg.

```php
<?php

use Radix\File\Reader;

Reader::csvStream('huge_data.csv', function (array $row): void {
    // Körs för varje rad i filen
    echo $row['email'] ?? '';
}, hasHeader: true);
```

---

## Skriva filer (Writer)

`Writer` gör det enkelt att spara data och skapar mappar om de saknas.

### JSON & text

```php
<?php

use Radix\File\Writer;

Writer::json('storage/output.json', [
    'status' => 'ok',
    'count' => 5,
]);

Writer::text('storage/logs/test.log', 'Händelse registrerad.');
```

### CSV

```php
<?php

use Radix\File\Writer;

$data = [
    ['id' => '1', 'email' => 'test@example.com'],
    ['id' => '2', 'email' => 'invalid-email'],
];

Writer::csv('storage/users.csv', $data, headers: ['id', 'email']);
```

### Streaming (skriva)

```php
<?php

use Radix\File\Writer;

Writer::csvStream('storage/export.csv', function (callable $writeRow): void {
    foreach ($largeDataset as $user) {
        $writeRow([$user->id, $user->email]);
    }
}, headers: ['ID', 'Email']);
```

---

## Encoding-stöd

Både Reader och Writer stödjer konvertering mellan teckenkodningar, vilket är användbart vid integration med äldre system.

```php
<?php

use Radix\File\Reader;
use Radix\File\Writer;

// Läs CP1252 och få UTF-8
$content = Reader::text('windows.txt', 'CP1252');

// Skriv UTF-8 till ISO-8859-1
Writer::text('export.csv', $utf8String, 'ISO-8859-1');
```
