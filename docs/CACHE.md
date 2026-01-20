# Cache (FileCache)

Radix tillhandahåller ett enkelt filbaserat cachesystem via klassen `Radix\Support\FileCache`. Det är idealiskt för att lagra beräkningstunga resultat eller API-svar temporärt.

## Grundläggande användning

Du kan lagra strängar, arrayer eller objekt. All data serialiseras automatiskt till JSON.

```php
use Radix\Support\FileCache;

$cache = new FileCache();

// Spara data i 1 timme (3600 sekunder)
$cache->set('weather_data', ['temp' => 22, 'unit' => 'C'], 3600);

// Hämta data
$weather = $cache->get('weather_data');

if ($weather === null) {
    // Cachen har gått ut eller finns inte
}
```

## TTL (Time To Live)

Du kan ange utgångstiden som ett heltal (sekunder) eller som ett `DateInterval`-objekt.

```php
// Spara i en vecka med DateInterval
$cache->set('key', $value, new DateInterval('P7D'));
```

## Hantering och städning

- **`delete(key)`**: Tar bort en specifik nyckel.
- **`clear()`**: Rensar hela cachen.
- **`prune()`**: Skannar mappen och tar bort alla filer som har gått ut. Detta bör köras regelbundet, till exempel via ett schemalagt jobb.
