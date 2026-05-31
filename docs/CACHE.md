# docs/CACHE.md

← [`Tillbaka till index`](INDEX.md)

# Cache (Radix App)

Radix App använder filbaserad cache via **Radix Framework**, framför allt:

```php
Radix\Support\FileCache
```

Cache används för att lagra temporär data på disk, till exempel:

- app-cache
- API-resultat
- beräkningstunga resultat
- health/status-data
- rate limit-data
- template/view-cache

---

## Översikt

Vanliga cache-paths i Radix App:

```text
cache/
  app/
  views/
  health/
  ratelimit/
```

Vanliga `.env`-nycklar:

```text
CACHE_ROOT=cache
VIEWS_CACHE_PATH=cache/views
APP_CACHE_PATH=cache/app
HEALTH_CACHE_PATH=cache/health
RATELIMIT_CACHE_PATH=cache/ratelimit
```

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## FileCache

`Radix\Support\FileCache` är ett enkelt filbaserat cachesystem.

Det stödjer:

- `get()`
- `set()`
- `delete()`
- `clear()`
- `prune()`
- TTL i sekunder
- TTL som `DateInterval`
- default-värde vid cache miss

---

## Grundläggande användning

```php
<?php

declare(strict_types=1);

use Radix\Support\FileCache;

$cache = new FileCache();

$cache->set('weather_data', [
    'temp' => 22,
    'unit' => 'C',
], 3600);

$weather = $cache->get('weather_data');

if ($weather === null) {
    // Cachen saknas eller har gått ut.
}
```

---

## Ange cache-katalog

Default är normalt:

```text
ROOT_PATH/cache/app
```

Du kan ange egen katalog:

```php
$cache = new FileCache(ROOT_PATH . '/cache/health');
```

Eller använda env-värde:

```php
$cache = new FileCache(getenv('APP_CACHE_PATH') ?: null);
```

---

## Spara cache

```php
$cache->set('key', 'value', 3600);
```

Spara array:

```php
$cache->set('user_stats', [
    'count' => 10,
    'active' => 8,
], 600);
```

Spara utan TTL:

```php
$cache->set('forever_key', [
    'value' => true,
]);
```

Utan TTL sparas värdet utan utgångstid.

---

## Hämta cache

```php
$value = $cache->get('key');
```

Med default-värde:

```php
$value = $cache->get('key', default: []);
```

Om filen saknas, är ogiltig eller har gått ut returneras default-värdet.

---

## TTL i sekunder

TTL anges i sekunder:

```php
$cache->set('key', 'value', 60);
```

Exempel:

```text
60    = 1 minut
3600  = 1 timme
86400 = 1 dygn
```

---

## TTL som DateInterval

```php
<?php

declare(strict_types=1);

use DateInterval;
use Radix\Support\FileCache;

$cache = new FileCache();

$cache->set('weekly', ['ok' => true], new DateInterval('P7D'));
```

---

## Radera en nyckel

```php
$cache->delete('weather_data');
```

Returnerar normalt `true` även om nyckeln redan saknas.

---

## Rensa cache

Rensa hela FileCache-katalogen:

```php
$cache->clear();
```

Det tar bort cachefiler i cache-katalogen.

---

## Prune

`prune()` skannar cache-katalogen och tar bort utgångna cachefiler.

```php
$cache->prune();
```

Det kan köras via cron/schemalagt jobb om du har mycket cache.

Exempel:

```php
$cache = new FileCache(ROOT_PATH . '/cache/app');
$cache->prune();
```

---

## Cacheformat

FileCache sparar data som `.cache`-filer.

Cache-nycklar saneras till filnamn.

Exempel:

```text
weather_data.cache
api_response_users.cache
```

Otillåtna tecken i nyckeln ersätts med `_`.

---

## Vad kan cachas?

FileCache använder JSON-baserad lagring.

Det passar bra för:

```text
strings
numbers
booleans
arrays
null
JSON-kompatibla värden
```

Undvik att cacha:

```text
resurser
closures
PDO-objekt
komplexa objekt som inte kan JSON-kodas korrekt
hemligheter/tokens i onödan
```

---

## Cache via container

Radix App kan registrera FileCache i containern.

Exempel:

```php
$cache = app(\Radix\Support\FileCache::class);
```

Då används normalt appens konfigurerade cache-path.

---

## Rensa cache via CLI

Rensa cache:

```bash
php radix cache:clear
```

Det rensar normalt:

- app-cache
- rate limiter-cache

Beroende på appens setup kan andra cachekataloger också påverkas av egna kommandon eller tooling.

---

## Vad `cache:clear` gör

`cache:clear` rensar appens cacheområden.

Typiskt:

```text
APP_CACHE_PATH
RATELIMIT_CACHE_PATH
```

Om `APP_CACHE_PATH` saknas används default:

```text
ROOT_PATH/cache/app
```

Om `RATELIMIT_CACHE_PATH` saknas används normalt:

```text
sys_get_temp_dir()/radix_ratelimit
```

Kommandot har skydd mot att rensa för breda kataloger som projektroten.

---

## När ska man rensa cache?

Rensa cache efter ändringar i:

- config
- services
- providers
- middleware
- routes
- templates
- scaffolds
- rate limit-policy
- environment settings

Kör:

```bash
php radix cache:clear
```

---

## Template/view-cache

Templates kompileras och cachas för prestanda.

View-cache path styrs normalt av:

```text
VIEWS_CACHE_PATH=cache/views
```

Om templateändringar inte syns:

```bash
php radix cache:clear
```

Beroende på implementation kan du även behöva ta bort view-cache manuellt eller köra ett mer specifikt cachekommando om appen har ett sådant.

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Rate limit-cache

Rate limiter använder cache för att hålla koll på antal requests.

Path:

```text
RATELIMIT_CACHE_PATH=cache/ratelimit
```

Om rate limit beter sig oväntat i development:

```bash
php radix cache:clear
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Health-cache

Health/status-data kan använda:

```text
HEALTH_CACHE_PATH=cache/health
```

Det är användbart för att inte köra dyra health checks för ofta.

Se mer i:

- [`API.md`](API.md)

---

## HTTP-cache

HTTP-cache headers är något annat än FileCache.

Exempel:

```text
Cache-Control
ETag
Last-Modified
```

Dessa styr webbläsare/CDN/proxy-cache.

För statiska assets hanteras cache ofta via `.htaccess` eller webbserver.

Se mer i:

- [`HTTP.md`](HTTP.md)
- [`FRONTEND.md`](FRONTEND.md)

---

## Cache och assets

För CSS/JS/bilder bör appen använda:

```php
versioned_file('/assets/css/app.css')
```

Exempel i template:

```html
<link rel="stylesheet" href="{{ versioned_file('/assets/css/app.css') }}">
<script src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Det hjälper webbläsaren att hämta rätt version efter deploy.

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)

---

## Cache och deploy

Inför eller efter deploy kan du köra:

```bash
npm run start:build
composer install --no-dev --optimize-autoloader
php radix cache:clear
```

Om du kör migrationer:

```bash
php radix migrations:migrate
```

Exakt deployflöde beror på miljö.

---

## Permissions

Cachekataloger måste vara skrivbara av PHP-processen.

Kontrollera:

```text
cache/
cache/app/
cache/views/
cache/health/
cache/ratelimit/
```

På Linux kan rättigheter behöva justeras.

Exempelprincip:

```bash
chmod -R ug+rw cache
```

Anpassa efter serverns användare/grupp.

---

## Säkerhet

Cache kan innehålla känslig data.

Tänk på:

- lägg inte cache publikt under `public/`
- cacha inte tokens/lösenord i onödan
- rensa cache vid environment-byte
- se till att cachefiler inte kan laddas via webben
- använd kort TTL för känsligare data

Rekommenderad placering:

```text
cache/
```

i projektroten, inte:

```text
public/cache/
```

---

## Testning

I tester kan du använda en temporär cachekatalog.

Exempel:

```php
$cache = new \Radix\Support\FileCache(sys_get_temp_dir() . '/radix-test-cache');

$cache->set('key', 'value', 60);

self::assertSame('value', $cache->get('key'));

$cache->clear();
```

---

## Bra praxis

- använd tydliga cache-nycklar
- sätt TTL på data som kan bli gammal
- cacha inte känslig data längre än nödvändigt
- använd `prune()` om cache växer mycket
- rensa cache efter config/template/scaffold-ändringar
- håll cache utanför `public/`
- använd `versioned_file()` för frontend-assets
- använd separata cachekataloger för app/cache/views/rate limit om möjligt

---

## Felsökning

### Cache ändras inte

Rensa:

```bash
php radix cache:clear
```

Kontrollera att du använder rätt cache-path.

### Cachefil skapas inte

Kontrollera:

- att katalogen finns
- att PHP har skrivrättighet
- att värdet går att JSON-koda

### `get()` returnerar default

Möjliga orsaker:

- nyckeln saknas
- TTL har gått ut
- cachefilen är trasig
- värdet kunde inte läsas
- fel cache-katalog används

### Rate limit släpper inte

Rensa rate limit-cache:

```bash
php radix cache:clear
```

Kontrollera:

```text
RATELIMIT_CACHE_PATH
```

### Templates ändras inte

Rensa cache:

```bash
php radix cache:clear
```

Kontrollera även:

```text
VIEWS_CACHE_PATH
```

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`CLI.md`](CLI.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`FRONTEND.md`](FRONTEND.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`HTTP.md`](HTTP.md)
- [`SECURITY.md`](SECURITY.md)
