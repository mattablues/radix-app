# docs/TESTING.md

← [`Tillbaka till index`](INDEX.md)

# Testning (Radix App)

Den här guiden beskriver hur du kör tester och kvalitetskontroller i **Radix App**.

Projektet använder:

- PHPUnit
- PHPStan
- PHP-CS-Fixer
- Infection
- Composer scripts

---

## Översikt

Vanliga kommandon:

```bash
composer format:check
composer stan
composer test
```

Valfritt:

```bash
composer infect
composer infect:pcov
composer infect:xdebug
```

Autoformat:

```bash
composer format
```

---

## Composer scripts

Radix App har scripts i `composer.json`.

Vanliga scripts:

```text
stan
stan:minimal
test
infect
infect:pcov
infect:xdebug
format
format:check
```

Rekommenderat är att köra via Composer scripts i stället för att komma ihåg långa vendor-kommandon.

---

## PHPUnit

Kör alla tester:

```bash
composer test
```

Det kör normalt PHPUnit med Xdebug avstängt:

```bash
php -d xdebug.mode=off vendor/bin/phpunit
```

---

## Kör en specifik testfil

```bash
vendor/bin/phpunit -c phpunit.xml tests/ExampleTest.php
```

Exempel:

```bash
vendor/bin/phpunit -c phpunit.xml tests/Controllers/HomeControllerTest.php
```

---

## Kör ett specifikt test med filter

```bash
vendor/bin/phpunit -c phpunit.xml --filter testName
```

Exempel:

```bash
vendor/bin/phpunit -c phpunit.xml --filter testUserCanLogin
```

---

## PHPStan

Full analys via projektets wrapper:

```bash
composer stan
```

Minimal analys:

```bash
composer stan:minimal
```

Direkt:

```bash
vendor/bin/phpstan analyse -c phpstan.minimal.neon
```

eller med projektets fulla config:

```bash
vendor/bin/phpstan analyse -c phpstan.neon.dist
```

---

## PHP-CS-Fixer

Kontrollera kodstil utan att ändra filer:

```bash
composer format:check
```

Auto-fixa kodstil:

```bash
composer format
```

Rekommenderat före commit:

```bash
composer format:check
```

Om den failar:

```bash
composer format
```

---

## Infection

Mutation testing körs med Infection.

Kör standard:

```bash
composer infect
```

Med PCOV:

```bash
composer infect:pcov
```

Med Xdebug:

```bash
composer infect:xdebug
```

Infection är tyngre än vanliga tester och körs ofta mer sällan, till exempel lokalt vid större ändringar eller schemalagt i CI.

---

## Rekommenderat före commit

Kör:

```bash
composer format:check
composer stan
composer test
```

Om kodstil behöver fixas:

```bash
composer format
composer format:check
```

Vid större ändringar kan du även köra:

```bash
composer infect
```

---

## Teststruktur

Tester ligger under:

```text
tests/
```

Vanlig struktur kan vara:

```text
tests/
  Controllers/
  Services/
  Models/
  Middleware/
  Feature/
  Unit/
```

Exakt struktur beror på appens testsetup och installerade scaffolds.

---

## Autoload för tester

Testnamespace konfigureras normalt i `composer.json`.

Exempel:

```json
{
  "autoload-dev": {
    "psr-4": {
      "Radix\\Tests\\": "tests"
    }
  }
}
```

Om du lägger till nya testklasser och autoload strular:

```bash
composer dump-autoload
```

---

## PHPUnit config

PHPUnit-konfiguration finns i:

```text
phpunit.xml
```

Den styr till exempel:

- test suites
- bootstrap
- coverage settings
- environment för tester

---

## Testmiljö

För tester bör du undvika att köra mot production-resurser.

Kontrollera:

```text
APP_ENV=test
APP_DEBUG=1 eller 0 beroende på testpolicy
DB_* för testdatabas
MAIL_* för fake/dev mail
```

Om tester använder `.env` bör du säkerställa att testvärden är säkra.

---

## Databastester

För tester som använder databas:

- använd separat testdatabas
- använd migrationer för schema
- använd seeders/testdata vid behov
- rensa mellan tester
- använd transaktioner om testsetup stödjer det

Exempel:

```bash
php radix migrations:migrate
composer test
```

Undvik att testa mot productiondatabas.

---

## Filtester

För tester som skriver filer:

- använd `sys_get_temp_dir()`
- skapa unik testkatalog
- rensa efter test
- testa både success och failure paths

Exempel:

```php
$dir = sys_get_temp_dir() . '/radix-test-' . bin2hex(random_bytes(4));

mkdir($dir, 0o755, true);

try {
    // test
} finally {
    // rensa
}
```

---

## Cachetester

Använd separat cachekatalog:

```php
$cache = new \Radix\Support\FileCache(
    sys_get_temp_dir() . '/radix-cache-test'
);
```

Rensa efter test:

```php
$cache->clear();
```

---

## Loggtester

Använd temporär loggkatalog:

```php
$logger = new \Radix\Support\Logger(
    channel: 'test',
    baseDir: sys_get_temp_dir() . '/radix-log-test'
);
```

---

## Mailtester

För mailflöden:

- mocka `MailManager`
- använd fake mailer
- testa listeners separat
- kontrollera template/data/options

Exempel på testfall:

```text
SendActivationEmailListener skickar rätt template
SendPasswordResetEmailListener skickar rätt mottagare
SendContactEmailListener sätter reply_to
```

---

## API-tester

Testa:

```text
statuskod
Content-Type
JSON-struktur
auth/token
valideringsfel
invalid JSON
CORS/preflight
rate limit
```

Exempel:

```text
GET /api/v1/health utan token -> 401 i production-policy
GET /api/v1/health med token -> 200
POST invalid JSON -> 400
POST invalid payload -> 422
```

Se mer i:

- [`API.md`](API.md)

---

## Controller-tester

Controller-tester bör kontrollera:

- rätt response
- rätt view
- rätt redirect
- valideringsfel
- auth/guest-flöden
- CSRF-policy
- att services anropas korrekt

Håll gärna affärslogik i services så controllers blir enklare att testa.

---

## Service-tester

Services är ofta lättare att testa än controllers.

Exempel:

```php
$service = new \App\Services\ReportService();

$result = $service->generate();

self::assertIsArray($result);
```

För services med dependencies:

- injicera fake/mock
- använd testdatabas
- använd temporära filer
- testa exceptions

---

## Middleware-tester

Middleware bör testas för:

```text
request stoppas korrekt
request släpps vidare korrekt
response modifieras korrekt
```

Exempel:

```text
guest till /dashboard -> redirect
icke-admin till /admin -> 403
för stor request -> 413
rate limit -> 429
```

---

## Event/listener-tester

Testa:

- att event dispatchas
- att listener gör rätt sak
- att listener hanterar errors
- att stopPropagation fungerar där det används

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Stabila tester

Rekommendationer:

- undvik tester som beror på riktig tid om möjligt
- undvik `sleep()` när det går
- använd deadlines/loopar om du måste vänta
- använd temporära kataloger
- nollställ statiskt state mellan tester
- mocka externa API:er
- undvik nätverksanrop i unit tests
- se till att tester kan köras i valfri ordning

---

## Externa tjänster

Mocka eller fake:a externa tjänster:

```text
SMTP
geolocation API
HTTP APIs
filesystem utanför temp
payment providers
```

Tester ska inte bli flakiga för att en extern tjänst är nere.

---

## Mutation testing

Infection ändrar små delar av koden och kontrollerar om testerna fångar ändringen.

Det hjälper dig hitta tester som bara kör kod utan att faktiskt verifiera beteende.

Kör:

```bash
composer infect
```

Eller med driver:

```bash
composer infect:pcov
composer infect:xdebug
```

---

## CI

I CI rekommenderas minst:

```bash
composer format:check
composer stan
composer test
```

Valfritt eller schemalagt:

```bash
composer infect
```

Se även:

```text
github-settings/
```

och CI-dokumentation i projektet.

---

## Felsökning

### PHPUnit hittar inte klass

Kör:

```bash
composer dump-autoload
```

Kontrollera namespace och filnamn.

### Tester passerar lokalt men inte i CI

Kontrollera:

- PHP-version
- extensions
- `.env`/env vars
- filrättigheter
- paths
- testordning
- timezone
- databasläge

### PHPStan klagar på dynamik

Lägg till tydligare typer:

```php
/** @var array<string, mixed> $data */
$data = $something;
```

eller förbättra return types.

### Infection är långsamt

Kör på mindre scope eller med PCOV/Xdebug enligt projektets scripts.

```bash
composer infect:pcov
```

### Kodstil failar

Kör:

```bash
composer format
```

och sedan:

```bash
composer format:check
```

---

## Bra praxis

- kör `composer format:check`, `composer stan`, `composer test` före commit
- skriv tester för både success och failure paths
- håll services testbara
- mocka externa tjänster
- använd temporära kataloger för filer/cache/loggar
- använd separat testdatabas
- undvik flakiga tidstester
- kör Infection vid större ändringar
- håll PHPStan-nivån grön

---

## Relaterat

- [`CLI.md`](CLI.md)
- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`API.md`](API.md)
- [`SECURITY.md`](SECURITY.md)
- [`LOGGING.md`](LOGGING.md)
