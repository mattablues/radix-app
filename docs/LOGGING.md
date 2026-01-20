# Loggning (Logger)

Loggningssystemet i Radix (`Radix\Support\Logger`) gör det enkelt att spara händelser till disk med olika allvarlighetsgrader. Det stödjer kanaler, logg-rotation och kontext-interpolering.

## Loggnivåer

Radix stödjer de vanligaste loggnivåerna:

```php
use Radix\Support\Logger;

$logger = new Logger('app'); // 'app' är kanalen

$logger->debug('Variabeln x är satt');
$logger->info('Användare loggade in', ['user_id' => 42]);
$logger->warning('Lågt lagringsutrymme');
$logger->error('Kunde inte ansluta till databasen');
```

## Kontext och Interpolering

Du kan skicka med en array som kontext. Om meddelandet innehåller platshållare (inuti `{}`) byts de automatiskt ut mot värden från kontexten. Övrig data sparas som JSON i slutet av loggraden.

```php
// Loggar: [Datum] app.INFO Användare Anna skapades {"role":"admin"}
$logger->info('Användare {name} skapades', [
    'name' => 'Anna',
    'role' => 'admin'
]);
```

## Rotation och Retention

Logger-klassen hanterar automatiskt filstorlekar och städning:

1.  **Rotation**: Om en loggfil överstiger maxgränsen (standard 10 MB) skapas en ny fil (t.ex. `app-2024-01-20.log.1`).
2.  **Retention**: Gamla loggfiler tas automatiskt bort efter ett visst antal dagar (standard 14 dagar).

Du kan konfigurera detta i konstruktorn:
```php
$logger = new Logger(
    channel: 'billing',
    maxBytes: 5 * 1024 * 1024, // 5 MB
    retentionDays: 30          // Spara i en månad
);
```