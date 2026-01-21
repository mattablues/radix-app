# Geo Location (GEOLOCATION.md)

Radix tillhandahåller en enkel tjänst för att hämta geografisk information baserat på IP-adresser via `ip-api.com`.

## Användning

Du kan använda `GeoLocator` för att automatiskt identifiera besökarens stad, land eller tidszon.

### Grundläggande exempel
Som standard hämtar tjänsten information för den aktuella besökaren (`REMOTE_ADDR`).

```php
use Radix\Support\GeoLocator;

$geo = new GeoLocator();
$location = $geo->getLocation();

echo $location['city'];    // t.ex. "Stockholm"
echo $location['country']; // t.ex. "Sweden"
```

### Hämta specifik IP eller fält
Du kan skicka med en specifik IP-adress eller hämta ett enskilt fält direkt:

```php
$geo = new GeoLocator();

// Hämta landskod för en specifik IP
$countryCode = $geo->get('countryCode', '8.8.8.8'); // "US"

// Hämta tidszon
$timezone = $geo->get('timezone'); // t.ex. "Europe/Stockholm"
```

## Felhantering
Om API:et inte kan nås eller om en ogiltig IP-adress skickas, kastas en `GeoLocatorException`.

```php
try {
    $location = $geo->getLocation('ogiltig-ip');
} catch (\Radix\Http\Exception\GeoLocatorException $e) {
    // Logga felet eller visa ett standardvärde
}
```

---

## Uppdatering av INDEX.md

Lägg till raden under sektionen **Kärnkoncept**:

- **[Geo Location](GEOLOCATION.md)**: Identifiera besökarens geografiska position via IP.