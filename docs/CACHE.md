# docs/CACHE.md

← [`Tillbaka till index`](INDEX.md)

# Cache (FileCache) (Radix App)

Radix tillhandahåller ett filbaserat cachesystem via `Radix\Support\FileCache`.  
Det passar bra för att lagra beräkningstunga resultat eller temporära API-svar.

---

## Grundläggande användning

Du kan lagra strängar, arrayer eller objekt. Data serialiseras automatiskt.

```php
<?php

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

---

## TTL (Time To Live)

TTL kan anges i sekunder eller som ett `DateInterval`-objekt.

```php
<?php

use DateInterval;
use Radix\Support\FileCache;

$cache = new FileCache();

// Spara i en vecka
$cache->set('key', ['a' => 1], new DateInterval('P7D'));
```

---

## Hantering och städning

- `delete($key)` — tar bort en specifik nyckel
- `clear()` — rensar hela cachen
- `prune()` — skannar cache-mappen och tar bort utgångna filer (bra att köra via schema/cron)

---

## Rensa cache via CLI

Om du vill rensa appens cache (t.ex. efter config/template-ändringar):

```bash
php radix cache:clear
```
Se även:

- [`docs/CLI.md`](CLI.md)
