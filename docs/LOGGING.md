# docs/LOGGING.md

← [`Tillbaka till index`](INDEX.md)

# Loggning (Radix App)

Radix App använder loggningsstöd från **Radix Framework** via:

```php
Radix\Support\Logger
```

Loggern skriver loggar till disk och stödjer:

- loggnivåer
- kanaler
- context
- interpolering
- filrotation
- retention/städning av gamla loggar

---

## Översikt

Standardplats för loggar är normalt:

```text
storage/logs/
```

Exempel på loggfiler:

```text
storage/logs/app-2026-05-31.log
storage/logs/health-2026-05-31.log
storage/logs/app-2026-05-31.log.1
```

Loggfilen innehåller normalt:

```text
[datum tid] channel.LEVEL message context
```

Exempel:

```text
[2026-05-31 12:34:56] app.INFO Användare Anna skapades {"role":"admin"}
```

---

## Skapa logger

```php
<?php

declare(strict_types=1);

use Radix\Support\Logger;

$logger = new Logger('app');
```

`app` är kanalnamnet.

---

## Loggnivåer

Loggern stödjer vanliga nivåer:

```php
$logger->debug('Debug message');

$logger->info('Info message');

$logger->warning('Warning message');

$logger->error('Error message');
```

---

## Exempel

```php
<?php

declare(strict_types=1);

use Radix\Support\Logger;

$logger = new Logger('app');

$logger->debug('Variabeln x är satt');

$logger->info('Användare loggade in', [
    'user_id' => 42,
]);

$logger->warning('Lågt lagringsutrymme');

$logger->error('Kunde inte ansluta till databasen');
```

---

## Kanaler

En kanal är ett namn för en viss typ av logg.

Exempel:

```php
$appLogger = new Logger('app');

$healthLogger = new Logger('health');

$billingLogger = new Logger('billing');
```

Det ger separata loggfiler, till exempel:

```text
app-2026-05-31.log
health-2026-05-31.log
billing-2026-05-31.log
```

Rekommendation:

- `app` för generella apphändelser
- `health` för health checks
- `auth` för autentiseringsrelaterade händelser
- `security` för säkerhetshändelser
- egna kanaler för brusiga områden

---

## Context

Du kan skicka context som array:

```php
$logger->info('Användare loggade in', [
    'user_id' => 42,
    'ip' => '127.0.0.1',
]);
```

Context loggas som JSON om det inte redan interpoleras i meddelandet.

---

## Interpolering

Om meddelandet innehåller placeholders med `{namn}` ersätts de från context.

Exempel:

```php
$logger->info('Användare {name} skapades', [
    'name' => 'Anna',
    'role' => 'admin',
]);
```

Loggas ungefär som:

```text
[2026-05-31 12:34:56] app.INFO Användare Anna skapades {"role":"admin"}
```

`name` används i meddelandet och tas därför bort från JSON-context.

`role` finns kvar som extra context.

---

## Vilka context-värden interpoleras?

Endast skalära värden och `null` interpoleras direkt.

Exempel på skalära värden:

```text
string
int
float
bool
null
```

Arrays och objekt loggas som JSON-context om de går att JSON-koda.

---

## Ange loggkatalog

Du kan ange egen loggkatalog:

```php
$logger = new Logger(
    channel: 'billing',
    baseDir: ROOT_PATH . '/storage/logs/billing'
);
```

Om katalogen inte finns försöker loggern skapa den.

---

## Rotation

Loggern kan rotera loggfiler när de blir för stora.

Default är normalt:

```text
10 MB per fil
```

Om filen blir större skapas suffix:

```text
app-2026-05-31.log.1
app-2026-05-31.log.2
```

Exempel med egen maxstorlek:

```php
$logger = new Logger(
    channel: 'billing',
    maxBytes: 5 * 1024 * 1024
);
```

---

## Retention

Loggern kan ta bort gamla loggar.

Default är normalt:

```text
14 dagar
```

Exempel:

```php
$logger = new Logger(
    channel: 'billing',
    retentionDays: 30
);
```

Städning sker enkelt/dagligen per logger-instans när loggning sker.

---

## Fullt exempel med rotation och retention

```php
<?php

declare(strict_types=1);

use Radix\Support\Logger;

$logger = new Logger(
    channel: 'billing',
    baseDir: ROOT_PATH . '/storage/logs',
    maxBytes: 5 * 1024 * 1024,
    retentionDays: 30
);

$logger->info('Invoice {invoice_id} paid', [
    'invoice_id' => 123,
    'amount' => 499,
    'currency' => 'SEK',
]);
```

---

## Logger via container

Radix App kan registrera loggern i containern.

Exempel:

```php
$logger = app(\Radix\Support\Logger::class);
```

Då används normalt appens standardkanal, ofta `app`.

För separata kanaler kan du skapa en ny logger:

```php
$logger = new \Radix\Support\Logger('health');
```

eller registrera kanal-specifik logger i provider/container.

Se mer i:

- [`SERVICES.md`](SERVICES.md)

---

## Loggning i services

Services är ofta bra plats för loggning.

Exempel:

```php
final class UserService
{
    public function __construct(
        private readonly \Radix\Support\Logger $logger,
    ) {}

    public function createUser(array $data): void
    {
        // skapa användare...

        $this->logger->info('User created', [
            'email' => $data['email'] ?? null,
        ]);
    }
}
```

---

## Loggning i listeners

Listeners kan logga sidoeffekter.

Exempel:

```php
final class SendContactEmailListener
{
    public function __construct(
        private readonly \Radix\Support\Logger $logger,
    ) {}

    public function __invoke(\App\Events\ContactFormEvent $event): void
    {
        // skicka mail...

        $this->logger->info('Contact mail sent', [
            'email' => $event->email,
        ]);
    }
}
```

---

## Request logging

Radix App kan ha middleware för request logging, till exempel:

```text
api.logger
```

Den kan logga:

- method
- URI
- status
- duration
- IP
- request id

Tänk på att inte logga känsliga headers eller body-data.

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Request ID

Om appen använder request-id middleware kan det vara bra att inkludera request id i loggar.

Exempel på context:

```php
$logger->info('Request handled', [
    'request_id' => $requestId,
    'path' => $request->uri,
]);
```

Det gör felsökning enklare.

---

## Health logging

Health checks kan vara brusiga.

Använd gärna separat kanal:

```php
$logger = new Logger('health');
```

Då hålls `app`-loggen renare.

---

## Säkerhet

Logga aldrig hemligheter.

Undvik att logga:

```text
lösenord
password_confirmation
API tokens
session cookies
CSRF tokens
authorization headers
private keys
fullständiga personuppgifter i onödan
```

Om du måste logga felsökning, maskera känsliga värden.

Exempel:

```php
$logger->warning('Failed login for {email}', [
    'email' => $email,
    'ip' => $request->ip(),
]);
```

Men logga inte:

```php
$logger->debug('Login data', $request->post);
```

---

## Felhantering

Loggern är designad för att inte krascha appen om loggning misslyckas.

Om loggfil inte kan skrivas ignoreras felet normalt.

Det betyder att om loggar saknas behöver du kontrollera filrättigheter och paths.

---

## Permissions

Loggkatalogen måste vara skrivbar av PHP-processen.

Kontrollera:

```text
storage/logs/
```

På Linux kan rättigheter behöva justeras:

```bash
chmod -R ug+rw storage/logs
```

Anpassa efter serverns användare/grupp.

---

## Production

I production:

- logga fel tydligt
- visa inte stack traces för användare
- använd `APP_DEBUG=0`
- rotera loggar
- håll retention rimlig
- undvik känsliga data i loggar
- överväg central loggning om appen växer

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Development

I development kan du använda mer detaljerad loggning.

Exempel:

```php
$logger->debug('Query result', [
    'rows' => $rows,
]);
```

Men undvik att vänja dig vid att logga secrets även lokalt.

---

## Loggning och events

Events/listeners är ett bra ställe att logga viktiga apphändelser.

Exempel:

```text
UserRegisteredEvent
UserBlockedEvent
UserPasswordEvent
ContactFormEvent
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Testning

Vid tester kan du:

- använda temporär loggkatalog
- kontrollera att loggfil skapas
- kontrollera att interpolering fungerar
- kontrollera att känslig data inte loggas

Exempel:

```php
$dir = sys_get_temp_dir() . '/radix-test-logs';

$logger = new \Radix\Support\Logger(
    channel: 'test',
    baseDir: $dir,
);

$logger->info('Hello {name}', [
    'name' => 'Test',
]);

// assert file exists / contains expected text
```

---

## Bra praxis

- använd separata kanaler för brusiga områden
- använd context för strukturerad information
- använd placeholders för viktiga värden
- logga inte secrets
- håll retention rimlig
- se till att `storage/logs` är skrivbar
- använd request-id vid felsökning
- logga på rätt nivå
- undvik stora payloads i loggar

---

## Felsökning

### Loggfil skapas inte

Kontrollera:

```text
storage/logs/
```

och filrättigheter.

### Fel kanal

Kontrollera hur loggern skapas:

```php
new Logger('app');
new Logger('health');
```

### Loggar roterar för ofta

Öka `maxBytes`:

```php
new Logger('app', maxBytes: 20 * 1024 * 1024);
```

### Gamla loggar tas inte bort

Kontrollera `retentionDays`.

Städning sker när loggern skriver och normalt högst en gång per dag per instans.

### Context syns inte

Om ett context-värde används i placeholder tas det bort från JSON-context.

Exempel:

```php
$logger->info('Hello {name}', ['name' => 'Anna']);
```

Här syns inte `name` igen som JSON eftersom den redan interpolerats.

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`EVENTS.md`](EVENTS.md)
- [`SERVICES.md`](SERVICES.md)
- [`SECURITY.md`](SECURITY.md)
- [`TESTING.md`](TESTING.md)
