# docs/GEOLOCATION.md

← [`Tillbaka till index`](INDEX.md)

# Geo Location (Radix App)

Radix App kan hämta geografisk information baserat på IP-adress via:

```php
Radix\Support\GeoLocator
```

GeoLocator använder normalt en extern HTTP-tjänst, till exempel:

```text
http://ip-api.com/json
```

Det kan användas för:

- enklare platsinformation
- systemhälsa/status
- lokala/regionala funktioner
- översikt/loggning med försiktighet
- location-baserad middleware

---

## Viktigt

IP-baserad geolocation är **best effort**.

Det kan bli fel på grund av:

- VPN
- proxy
- mobilnät
- företagsnät
- felaktiga IP-databaser
- reverse proxy/load balancer
- externa API-problem

Använd därför inte IP-geolocation som enda säkerhetskritiska kontroll.

---

## Konfiguration

Vanliga `.env`-nycklar:

```dotenv
GEOLOCATOR_ENABLED=0
GEOLOCATOR_BASE_URL=http://ip-api.com/json
GEOLOCATOR_TIMEOUT=2
```

Relaterade locator-värden:

```dotenv
LOCATOR_COUNTRY=Sweden
LOCATOR_CITY=Stockholm
LOCATOR_CITY_URL=https://www.klart.se/se/stockholms-l%C3%A4n/stockholm/
```

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## `GEOLOCATOR_ENABLED`

Aktiverar/inaktiverar geolocation-flöden i appen.

Av:

```dotenv
GEOLOCATOR_ENABLED=0
```

På:

```dotenv
GEOLOCATOR_ENABLED=1
```

Andra sanna värden kan också accepteras beroende på appens middleware:

```text
true
yes
on
```

Rekommenderat default:

```dotenv
GEOLOCATOR_ENABLED=0
```

---

## `GEOLOCATOR_BASE_URL`

Bas-URL till geolocation-tjänsten:

```dotenv
GEOLOCATOR_BASE_URL=http://ip-api.com/json
```

GeoLocator bygger normalt URL så här:

```text
{GEOLOCATOR_BASE_URL}/{ip}
```

Exempel:

```text
http://ip-api.com/json/8.8.8.8
```

---

## `GEOLOCATOR_TIMEOUT`

Timeout i sekunder:

```dotenv
GEOLOCATOR_TIMEOUT=2
```

Rekommendation:

```text
1-3 sekunder
```

Sätt alltid timeout så att appen inte hänger länge om extern tjänst är nere.

---

## Grundläggande användning

```php
<?php

declare(strict_types=1);

use Radix\Support\GeoLocator;

$geo = new GeoLocator();

$location = $geo->getLocation();

echo $location['city'] ?? '';
echo $location['country'] ?? '';
```

Om ingen IP anges används normalt:

```text
$_SERVER['REMOTE_ADDR']
```

---

## Hämta specifik IP

```php
<?php

declare(strict_types=1);

use Radix\Support\GeoLocator;

$geo = new GeoLocator();

$location = $geo->getLocation('8.8.8.8');
```

---

## Hämta ett fält

```php
$countryCode = $geo->get('countryCode', '8.8.8.8');

$timezone = $geo->get('timezone');
```

Exempel på fält från ip-api kan vara:

```text
country
countryCode
region
regionName
city
zip
lat
lon
timezone
isp
org
query
```

Exakt fält beror på API-tjänsten.

---

## Ange base URL och timeout i kod

Du kan skapa GeoLocator med egna värden:

```php
$geo = new \Radix\Support\GeoLocator(
    baseUrl: 'http://ip-api.com/json',
    timeout: 2
);
```

Om inget anges används env/defaults.

---

## Felhantering

GeoLocator kan kasta exception om:

- API inte kan nås
- API returnerar ogiltig respons
- API returnerar status != success
- IP är ogiltig enligt API-tjänsten

Exempel:

```php
<?php

declare(strict_types=1);

use Radix\Support\GeoLocator;

try {
    $geo = new GeoLocator();

    $location = $geo->getLocation('8.8.8.8');
} catch (\Throwable $e) {
    // Logga felet eller använd fallback.
    $location = [
        'country' => null,
        'city' => null,
    ];
}
```

---

## Location middleware

Radix App kan ha en middleware som använder GeoLocator.

Exempel på alias:

```text
location
```

Den kan kontrollera:

```text
LOCATOR_COUNTRY
LOCATOR_CITY
```

och jämföra med besökarens geolocation.

Om appen är i development/local/test eller `GEOLOCATOR_ENABLED=0` bör middleware normalt släppa igenom requesten utan kontroll.

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Exempel: middleware-policy

Ett möjligt flöde:

```text
request kommer in
  ↓
GEOLOCATOR_ENABLED kontrolleras
  ↓
development/local/test släpps igenom
  ↓
GeoLocator hämtar plats
  ↓
country/city jämförs med LOCATOR_COUNTRY/LOCATOR_CITY
  ↓
request släpps igenom eller redirectas
```

Det här ska ses som en app-policy, inte en säkerhetsgaranti.

---

## Trusted proxy och IP

GeoLocator använder normalt klient-IP.

Om appen ligger bakom reverse proxy/load balancer kan IP bli fel om trusted proxy-konfiguration saknas.

Relaterad env:

```dotenv
TRUSTED_PROXY=
```

Om IP är fel påverkas:

- geolocation
- rate limiting
- allowlists
- logs
- audit trails

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Privacy och GDPR

IP-adresser kan vara personuppgifter.

Tänk på:

- informera användaren om relevant
- logga inte mer än nödvändigt
- spara inte rå geolocation-data längre än nödvändigt
- använd endast geolocation för tydliga syften
- undvik att skicka persondata till externa tjänster i onödan

---

## Caching

Geolocation kan cacheas för att minska externa anrop.

Exempelprincip:

```text
cache key = geo_{ip}
TTL = 1-24 timmar beroende på behov
```

Exempel:

```php
$cache = new \Radix\Support\FileCache(ROOT_PATH . '/cache/geo');

$key = 'geo_' . str_replace([':', '.'], '_', $ip);

$location = $cache->get($key);

if ($location === null) {
    $geo = new \Radix\Support\GeoLocator();
    $location = $geo->getLocation($ip);

    $cache->set($key, $location, 3600);
}
```

Se mer i:

- [`CACHE.md`](CACHE.md)

---

## Rate limits hos extern tjänst

Gratis geolocation-tjänster har ofta begränsningar.

Tänk på:

- rate limits
- uptime
- krav på attribution
- HTTP vs HTTPS
- GDPR/personuppgifter
- användarvillkor

Om geolocation är viktigt bör du överväga en betald/stabil tjänst.

---

## Säkerhet

Använd inte geolocation som enda skydd för känsliga endpoints.

Bättre skydd:

- auth
- roll/behörighet
- IP allowlist
- rate limiting
- token
- server-side validering

Geolocation kan vara ett extra lager, men inte ett primärt säkerhetslager.

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Loggning

Logga sparsamt.

Bra:

```php
$logger->info('Geo lookup failed', [
    'reason' => $e->getMessage(),
]);
```

Var försiktig med att logga full IP och komplett geodata om det inte behövs.

Se mer i:

- [`LOGGING.md`](LOGGING.md)

---

## Development

I development rekommenderas:

```dotenv
GEOLOCATOR_ENABLED=0
```

eller att middleware släpper igenom requests i:

```text
development
local
test
```

Det gör lokal utveckling snabbare och stabilare.

---

## Production

I production:

```dotenv
GEOLOCATOR_ENABLED=1
GEOLOCATOR_BASE_URL=http://ip-api.com/json
GEOLOCATOR_TIMEOUT=2
```

Men använd bara om appen faktiskt behöver geolocation.

Sätt fallback så att externa API-problem inte gör hela appen obrukbar.

---

## Felsökning

### All geolocation returnerar fel plats

Kontrollera:

- `REMOTE_ADDR`
- reverse proxy/load balancer
- `TRUSTED_PROXY`
- VPN/proxy
- extern geolocation-tjänst

### API nås inte

Kontrollera:

```dotenv
GEOLOCATOR_BASE_URL
GEOLOCATOR_TIMEOUT
```

Kontrollera även att servern får göra utgående HTTP-anrop.

### Appen blir långsam

Sänk timeout:

```dotenv
GEOLOCATOR_TIMEOUT=1
```

eller cachea resultat.

### Location middleware redirectar oväntat

Kontrollera:

```dotenv
GEOLOCATOR_ENABLED
LOCATOR_COUNTRY
LOCATOR_CITY
APP_ENV
```

Kom ihåg att IP-geolocation inte är exakt.

### Fungerar lokalt men inte production

Kontrollera:

- firewall/egress
- DNS
- extern API-tjänst
- proxy
- HTTPS/HTTP-policy
- rate limits

---

## Bra praxis

- ha `GEOLOCATOR_ENABLED=0` som default
- sätt kort timeout
- använd fallback
- cachea om det används ofta
- logga sparsamt
- informera användare vid behov
- använd inte geolocation som enda säkerhetskontroll
- kontrollera trusted proxy om appen kör bakom proxy
- respektera extern tjänsts villkor

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`CACHE.md`](CACHE.md)
- [`LOGGING.md`](LOGGING.md)
- [`SECURITY.md`](SECURITY.md)
