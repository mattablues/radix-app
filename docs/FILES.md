# Filhantering (Reader & Writer)

Radix tillhandahåller ett strömlinjeformat API för att läsa och skriva filer i olika format (JSON, CSV, XML, Text) via klasserna `Radix\File\Reader` och `Radix\File\Writer`.

## Läsa filer (Reader)

`Reader`-klassen erbjuder statiska metoder för att snabbt läsa in data till PHP-strukturer.

### JSON & Text
```php
use Radix\File\Reader;

// Läs JSON till en associativ array
$data = Reader::json('data.json');

// Läs råtext med automatisk konvertering till UTF-8
$text = Reader::text('legacy.txt', 'ISO-8859-1');
```

### CSV & XML
```php
// Läs CSV med headers (mappas automatiskt till assoc-arrayer)
$rows = Reader::csv('users.csv', delimiter: ';', hasHeader: true);

// Läs XML till en array
$config = Reader::xml('config.xml', assoc: true);
```

### Streaming (för stora filer)
För att hantera stora filer utan att använda för mycket minne kan du använda streaming:
```php
Reader::csvStream('huge_data.csv', function(array $row) {
    // Körs för varje rad i filen
    echo $row['email'];
}, hasHeader: true);
```

## Skriva filer (Writer)

`Writer`-klassen gör det enkelt att spara data och skapar automatiskt mappar som saknas.

### JSON & Text
```php
use Radix\File\Writer;

// Skriv snyggt formaterad JSON
Writer::json('storage/output.json', ['status' => 'ok', 'count' => 5]);

// Skriv råtext
Writer::text('logs/test.log', 'Händelse registrerad.');
```

### CSV med validering
Du kan validera din data mot ett schema innan den skrivs till CSV:
```php
$data = [
    ['id' => '1', 'email' => 'test@example.com'],
    ['id' => '2', 'email' => 'invalid-email']
];

$schema = [
    'required' => ['id', 'email'],
    'types' => ['id' => 'int']
];

// Validerar och skriver till fil
Writer::csv('storage/users.csv', $data, headers: ['id', 'email']);
```

### Streaming (Skriva)
```php
Writer::csvStream('export.csv', function($writeRow) {
    foreach ($largeDataset as $user) {
        $writeRow([$user->id, $user->email]);
    }
}, headers: ['ID', 'Email']);
```

## Encoding-stöd
Både Reader och Writer stödjer konvertering mellan olika teckenkodningar. Detta är användbart vid integration med äldre system som t.ex. använder `ISO-8859-1`.

```php
// Läser från Windows-format och returnerar UTF-8
$content = Reader::text('windows.txt', 'CP1252');

// Skriver UTF-8 sträng till fil i ISO-8859-1 format
Writer::text('export.csv', $utf8String, 'ISO-8859-1');
```