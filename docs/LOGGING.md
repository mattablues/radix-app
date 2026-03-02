# docs/LOGGING.md

← [`Tillbaka till index`](INDEX.md)

# Loggning (Logger) (Radix App)

Radix har ett loggningssystem via `Radix\Support\Logger` som gör det enkelt att skriva loggar till disk med olika nivåer.

Det stödjer bland annat:

- loggnivåer (debug/info/warning/error)
- kanaler (t.ex. `app`, `health`, `billing`)
- rotation/retention
- kontext + interpolering

---

## Loggnivåer

```php
<?php

use Radix\Support\Logger;

$logger = new Logger('app'); // 'app' är kanalen

$logger->debug('Variabeln x är satt');
$logger->info('Användare loggade in', ['user_id' => 42]);
$logger->warning('Lågt lagringsutrymme');
$logger->error('Kunde inte ansluta till databasen');
```

---

## Kontext och interpolering

Du kan skicka med en array som kontext. Om meddelandet innehåller platshållare (`{...}`) byts de ut mot värden från kontexten. Övrig data loggas som JSON.

```php
<?php

use Radix\Support\Logger;

$logger = new Logger('app');

// Loggar ungefär: [Datum] app.INFO Användare Anna skapades {"role":"admin"}
$logger->info('Användare {name} skapades', [
    'name' => 'Anna',
    'role' => 'admin',
]);
```

---

## Rotation och retention

Logger-klassen kan hantera logg-rotation (t.ex. när filen blir för stor) och retention (ta bort gamla filer efter X dagar).

Du kan konfigurera detta i konstruktorn:

```php
<?php

use Radix\Support\Logger;

$logger = new Logger(
    channel: 'billing',
    maxBytes: 5 * 1024 * 1024, // 5 MB
    retentionDays: 30          // Spara i 30 dagar
);
```

---

## Tips

- Skapa separata kanaler för “brusiga” områden (t.ex. `health`) så att `app`-loggen förblir läsbar.
- Logga aldrig hemligheter (tokens, nycklar, lösenord).
- I production: logga fel tydligt, men undvik att exponera stack traces till slutanvändaren (se [`docs/SECURITY.md`](SECURITY.md)).
