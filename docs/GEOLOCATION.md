# docs/GEOLOCATION.md

← [`Tillbaka till index`](INDEX.md)

# Geo Location (Radix App)

Radix kan hämta geografisk information baserat på IP-adresser via en enkel tjänst (t.ex. mot `ip-api.com`).

Det kan vara användbart för:
- systemhälsa/status (om du visar “plats”)
- väder/platsfunktioner
- loggning/översikt (med försiktighet)

---

## Användning

Du kan använda `GeoLocator` för att identifiera besökarens stad, land eller tidszon.

### Grundexempel

Som standard hämtar tjänsten info för den aktuella besökaren (via `REMOTE_ADDR`).

```php
<?php

use Radix\Support\GeoLocator;

$geo = new GeoLocator();
$location = $geo->getLocation();

echo $location['city'];    // t.ex. "Stockholm"
echo $location['country']; // t.ex. "Sweden"
```

---

## Hämta specifik IP eller ett fält

```php
<?php

use Radix\Support\GeoLocator;

$geo = new GeoLocator();

// Hämta landskod för en specifik IP
$countryCode = $geo->get('countryCode', '8.8.8.8'); // t.ex. "US"

// Hämta tidszon för aktuell besökare
$timezone = $geo->get('timezone'); // t.ex. "Europe/Stockholm"
```

---

## Felhantering

Om API:et inte kan nås eller om en ogiltig IP-adress skickas kan en exception kastas.

```php
<?php

use Radix\Support\GeoLocator;

try {
    $geo = new GeoLocator();
    $location = $geo->getLocation('ogiltig-ip');
} catch (\Throwable $e) {
    // Logga felet eller använd ett standardvärde
}
```

---

## Säkerhet & integritet (rekommendation)

- Behandla IP-baserad geo som “best effort” (kan vara fel)
- Logga inte mer än du behöver
- Sätt timeouts och fallbacks (API:et kan vara nere)

Se även:

- [`docs/SECURITY.md`](SECURITY.md)
- [`docs/LOGGING.md`](LOGGING.md)
