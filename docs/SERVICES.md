# docs/SERVICES.md

← [`Tillbaka till index`](INDEX.md)

# Services & Dependency Injection (Radix App)

Radix App använder en Dependency Injection-container för att hantera objekt och deras beroenden.

Det gör koden:

- mer testbar
- mer modulär
- enklare att underhålla
- enklare att konfigurera
- lättare att utöka via providers och scaffolds

---

## Översikt

Radix App använder främst tre delar för service-setup:

```text
config/services.php
config/providers.php
src/Providers/
```

Appens egna services ligger normalt i:

```text
src/Services/
```

Exempel:

```text
src/Services/AuthService.php
src/Services/UploadService.php
src/Services/ProfileAvatarService.php
src/Services/HealthCheckService.php
```

Exakt vilka services som finns beror på installerade scaffolds.

---

## Container

Containern är appens centrala registry för services.

Den kan innehålla till exempel:

- config
- database connection
- database manager
- router
- request/response
- session
- template viewer
- mailer
- logger
- event dispatcher
- CLI commands
- app services

---

## `config/services.php`

`config/services.php` är appens huvudsakliga container-boot.

Där sker typiskt:

- `.env` laddas
- environment valideras
- config-filer slås ihop
- container skapas
- core services registreras
- databas kopplas
- session konfigureras
- template viewer registreras
- CLI-kommandon registreras
- app services registreras

Det här är en viktig fil, men den bör inte bli platsen för all app-logik.

För mer avgränsad app-wiring rekommenderas service providers.

---

## Config i containern

Appens sammanslagna config registreras normalt som:

```text
config
```

och som klass:

```php
Radix\Config\Config
```

Exempel på hämtning:

```php
$config = app(\Radix\Config\Config::class);
```

eller via helper om appen har en sådan:

```php
$value = config('app.name');
```

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## Service Providers

Service providers används för att samla wiring för specifika delar av appen.

Providers ligger i:

```text
src/Providers/
```

och registreras i:

```text
config/providers.php
```

Exempel på providers:

```text
ConsoleCommandsServiceProvider
EventServiceProvider
ListenersServiceProvider
MailServiceProvider
RadixOverridesServiceProvider
```

---

## `config/providers.php`

Provider-listan kan se ut så här:

```php
<?php

declare(strict_types=1);

return [
    \App\Providers\RadixOverridesServiceProvider::class,
    \App\Providers\EventServiceProvider::class,
    \App\Providers\MailServiceProvider::class,
    \App\Providers\ListenersServiceProvider::class,
    \App\Providers\ConsoleCommandsServiceProvider::class,
];
```

Ordningen kan spela roll om en provider bygger på något som registreras av en tidigare provider.

---

## Skapa provider

Via CLI:

```bash
php radix make:provider MyServiceProvider
```

Kör hjälp:

```bash
php radix make:provider --help
```

Providern skapas normalt under:

```text
src/Providers/
```

Efter att du skapat en provider behöver den registreras i:

```text
config/providers.php
```

---

## Exempel på Service Provider

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ReportService;
use Radix\ServiceProvider\ServiceProviderInterface;

final class ReportServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $container = app();

        $container->addShared(ReportService::class, function () {
            return new ReportService(
                exportPath: ROOT_PATH . '/storage/reports'
            );
        });
    }
}
```

Registrera i `config/providers.php`:

```php
return [
    // ... andra providers
    \App\Providers\ReportServiceProvider::class,
];
```

---

## Services

Services används för affärslogik eller återanvändbar app-logik.

Vanliga exempel:

```text
AuthService
UploadService
ProfileAvatarService
HealthCheckService
UserService
ReportService
```

Services bör hålla controllers tunna.

Controller:

```text
ta emot request
validera input
anropa service
returnera response
```

Service:

```text
utför affärslogik
pratar med modeller/repositories
dispatchar event vid behov
hanterar transaktioner
```

---

## Skapa service

Via CLI:

```bash
php radix make:service UserService
```

Kör hjälp:

```bash
php radix make:service --help
```

Services skapas normalt under:

```text
src/Services/
```

---

## Exempel på service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class UserService
{
    public function createUser(array $data): User
    {
        $user = new User([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
        ]);

        $user->save();

        return $user;
    }
}
```

---

## Registrera service i container

För enkla services kan autowiring fungera beroende på container/setup.

För tydlig wiring kan du registrera service manuellt:

```php
$container->addShared(\App\Services\UserService::class, function () {
    return new \App\Services\UserService();
});
```

Om servicen behöver dependencies:

```php
$container->addShared(\App\Services\ProfileAvatarService::class, function () use ($container) {
    $uploadService = $container->get(\App\Services\UploadService::class);

    if (!$uploadService instanceof \App\Services\UploadService) {
        throw new \RuntimeException('Container returned invalid UploadService instance.');
    }

    return new \App\Services\ProfileAvatarService($uploadService);
});
```

---

## `add()` vs `addShared()`

Containern kan registrera services på olika sätt.

Vanligt:

```php
$container->add(SomeClass::class, SomeClass::class);
```

eller:

```php
$container->add(SomeClass::class, function () {
    return new SomeClass();
});
```

För delade singletons/shared services:

```php
$container->addShared(SomeClass::class, function () {
    return new SomeClass();
});
```

Förenklat:

```text
add()       = ny eller resolver-baserad instans beroende på container
addShared() = samma delade instans återanvänds
```

Använd `addShared()` för services där samma instans bör återanvändas, till exempel config, logger, viewer, event dispatcher eller stateless app-services.

---

## Konstruktorinjektion

Rekommenderat sätt att använda services är konstruktorinjektion.

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(): Response
    {
        return $this->view('users.index', [
            'users' => $this->users->all(),
        ]);
    }
}
```

Det gör controllern lättare att testa.

---

## Service locator med `app()`

Det går också att hämta services manuellt:

```php
$mailer = app(\Radix\Mailer\MailManager::class);
```

eller:

```php
$uploads = app(\App\Services\UploadService::class);
```

Det kan vara praktiskt i vissa helpers eller bootstrap-lägen, men i controllers/services är konstruktorinjektion oftast bättre.

---

## Vanliga helpers

Radix App kan ha helpers som använder containern.

Exempel:

```text
app()
config()
request()
view()
route()
redirect()
response()
```

Exakt helperstöd beror på app/framework-version.

---

## Services i controllers

Bra mönster:

```php
public function store(): Response
{
    $this->before();

    $form = new UserRequest($this->request);

    if (!$form->validate()) {
        return $this->view('users.create', [
            'errors' => $form->errors(),
        ]);
    }

    $user = $this->users->createUser($form->validated());

    return redirect(route('users.show', ['id' => $user->id]));
}
```

Controllern gör:

```text
CSRF/preflight
validering
anropar service
returnerar response
```

Servicen gör:

```text
affärslogik
databasoperation
event/loggning vid behov
```

---

## Services och events

Services kan dispatcha events när något händer.

Exempel:

```php
final class UserService
{
    public function __construct(
        private readonly \Radix\EventDispatcher\EventDispatcher $events,
    ) {}

    public function createUser(array $data): User
    {
        $user = new User($data);
        $user->save();

        $this->events->dispatch(new \App\Events\UserCreatedEvent($user));

        return $user;
    }
}
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Services och transaktioner

Om en service gör flera beroende writes bör du använda transaktion.

Exempel:

```php
User::transaction(function () use ($data): void {
    $user = new User($data);
    $user->save();

    AuditLog::from('audit_logs')->insert([
        'event' => 'user.created',
        'user_id' => $user->id,
    ])->execute();
});
```

Se mer i:

- [`ORM.md`](ORM.md)
- [`DATABASE.md`](DATABASE.md)

---

## Services och uploads

Uppladdningslogik bör ligga i en service.

Exempel:

```text
UploadService
ProfileAvatarService
```

Det håller controllers rena och gör uploadflödet lättare att testa.

Se mer i:

- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)

---

## Framework services

Radix Framework tillhandahåller generella services/komponenter som appen kan registrera och använda:

```text
Radix\Config\Config
Radix\Database\Connection
Radix\Database\DatabaseManager
Radix\Database\Migration\Migrator
Radix\Database\Migration\SeederRunner
Radix\EventDispatcher\EventDispatcher
Radix\Session\SessionInterface
Radix\Viewer\TemplateViewerInterface
Radix\Support\Logger
Radix\Support\FileCache
Radix\Console\CommandsRegistry
```

---

## App services

Radix App lägger till egna services, till exempel:

```text
App\Services\UploadService
App\Services\ProfileAvatarService
App\Services\AuthService
App\Services\HealthCheckService
```

Vissa kan bara registreras om klassen finns, vilket gör att scaffolds kan lägga till funktionalitet stegvis.

---

## Scaffolds och services

Scaffolds kan lägga till:

- services
- providers
- config
- controllers
- listeners
- middleware

Efter scaffold-installation kan du behöva rensa cache:

```bash
php radix cache:clear
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Miljövariabler och services

Känslig och miljöspecifik konfiguration ska ligga i `.env`.

Bra tumregel:

```text
.env          = secrets och miljöspecifika värden
config/*.php = struktur och defaults
services     = använder config/env via config-lagret
```

Exempel:

```php
$path = getenv('APP_CACHE_PATH') ?: 'cache/app';
```

För större services är det ofta bättre att läsa config en gång i provider och skicka in tydliga värden i konstruktorn.

---

## Testbarhet

Services gör koden lättare att testa.

I tester kan du:

- instansiera servicen direkt
- mocka dependencies
- använda testdatabas
- använda fake logger/event dispatcher
- testa utan HTTP/router

Exempel:

```php
$service = new UserService();

$user = $service->createUser([
    'name' => 'Test',
    'email' => 'test@example.com',
]);

self::assertSame('test@example.com', $user->email);
```

---

## Bra praxis

- håll controllers tunna
- lägg affärslogik i services
- lägg wiring i providers
- använd konstruktorinjektion
- använd `app()` sparsamt i appkod
- använd `addShared()` för delade/stateless services
- läs secrets från `.env`, inte hårdkodat i services
- håll services fokuserade
- skapa interfaces om du behöver byta implementation
- testa services separat där det är möjligt

---

## Felsökning

### Service hittas inte

Kontrollera att den är registrerad i containern eller kan autowiras.

Kontrollera:

```text
config/services.php
src/Providers/
config/providers.php
```

### Provider körs inte

Kontrollera att providern finns i:

```text
config/providers.php
```

Rensa cache:

```bash
php radix cache:clear
```

### Dependency injection misslyckas

Kontrollera:

- att dependency är registrerad
- att type-hint är korrekt
- att klassen autoloadas
- att constructor inte kräver okända scalarvärden

Kör vid behov:

```bash
composer dump-autoload
```

### Config saknas

Kontrollera:

```text
.env
config/*.php
```

Rensa cache:

```bash
php radix cache:clear
```

### Circular dependency

Om service A behöver B och B behöver A får du cirkulärt beroende.

Lös genom att:

- flytta gemensam logik till service C
- dispatcha event istället
- injicera factory/callable
- förenkla designen

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`EVENTS.md`](EVENTS.md)
- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)
- [`IMAGES.md`](IMAGES.md)
- [`TESTING.md`](TESTING.md)
