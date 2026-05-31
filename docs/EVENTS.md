# docs/EVENTS.md

← [`Tillbaka till index`](INDEX.md)

# Events & listeners (Radix App)

Radix App använder en `EventDispatcher` från **Radix Framework** för att hantera events och listeners.

Det gör att appen kan reagera på händelser utan att blanda in all sidoeffekt-logik direkt i controllers eller services.

Exempel:

```text
kontaktformulär skickas
  ↓
ContactFormEvent dispatchas
  ↓
SendContactEmailListener skickar mail
```

---

## Översikt

Begrepp:

```text
Event       = en klass som bär data om något som hänt
Listener    = en klass/callable som reagerar på eventet
Dispatcher  = komponenten som skickar eventet till listeners
Config      = kopplar event till listeners
```

Vanliga platser:

```text
src/Events/
src/EventListeners/
config/listeners.php
config/listeners.auth.php
src/Providers/ListenersServiceProvider.php
```

---

## Var events ligger

Events ligger normalt i:

```text
src/Events/
```

Exempel:

```text
ContactFormEvent.php
UserBlockedEvent.php
UserPasswordEvent.php
UserRegisteredEvent.php
```

Vilka events som finns beror på appens scaffolds och funktioner.

---

## Var listeners ligger

Listeners ligger normalt i:

```text
src/EventListeners/
```

Exempel:

```text
LogoutListener.php
SendActivationEmailListener.php
SendContactEmailListener.php
SendPasswordResetEmailListener.php
```

---

## Listener-konfiguration

Listeners registreras via config-filer.

Vanligt:

```text
config/listeners.php
```

Scaffolds kan lägga till extra listener-filer, till exempel:

```text
config/listeners.auth.php
```

Appens provider kan ladda flera listener-filer om de finns.

---

## Provider för listeners

Registrering sker normalt via en provider:

```text
src/Providers/ListenersServiceProvider.php
```

Den läser listener-config och registrerar handlers i:

```php
Radix\EventDispatcher\EventDispatcher
```

Providern aktiveras via:

```text
config/providers.php
```

---

## Skapa en event

Via CLI:

```bash
php radix make:event UserRegisteredEvent
```

Kör hjälp:

```bash
php radix make:event --help
```

Events skapas normalt under:

```text
src/Events/
```

---

## Exempel: event

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

final class UserRegisteredEvent extends Event
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $activationLink,
    ) {}
}
```

Ett event bör vara enkelt.

Det ska bära data, inte utföra affärslogik.

---

## Skapa en listener

Via CLI:

```bash
php radix make:listener SendActivationEmailListener
```

Kör hjälp:

```bash
php radix make:listener --help
```

Listeners skapas normalt under:

```text
src/EventListeners/
```

---

## Exempel: listener

En listener är ofta en invokable klass med `__invoke()`.

```php
<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\UserRegisteredEvent;
use Radix\Mailer\MailManager;

final readonly class SendActivationEmailListener
{
    public function __construct(
        private MailManager $mailManager,
    ) {}

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->mailManager->send(
            $event->email,
            'Välkommen!',
            "Klicka här: {$event->activationLink}"
        );
    }
}
```

---

## Registrera listeners

Exempel i `config/listeners.php`:

```php
<?php

declare(strict_types=1);

return [
    \App\Events\ContactFormEvent::class => [
        [
            'listener' => \App\EventListeners\SendContactEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 5,
        ],
    ],
];
```

Exempel i `config/listeners.auth.php`:

```php
<?php

declare(strict_types=1);

return [
    \App\Events\UserRegisteredEvent::class => [
        [
            'listener' => \App\EventListeners\SendActivationEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 10,
        ],
    ],
];
```

---

## Listener config-format

Varje handler kan innehålla:

```text
listener
type
dependencies
priority
stopPropagation
```

Exempel:

```php
[
    'listener' => \App\EventListeners\SendActivationEmailListener::class,
    'type' => 'custom',
    'dependencies' => [\Radix\Mailer\MailManager::class],
    'priority' => 10,
    'stopPropagation' => false,
]
```

---

## `type: container`

Används när listenern ska hämtas direkt från containern.

```php
[
    'listener' => \App\EventListeners\LogoutListener::class,
    'type' => 'container',
    'priority' => 20,
]
```

Det kräver att listenern kan lösas av containern.

---

## `type: custom`

Används när listenern ska skapas manuellt med dependencies från containern.

```php
[
    'listener' => \App\EventListeners\SendActivationEmailListener::class,
    'type' => 'custom',
    'dependencies' => [\Radix\Mailer\MailManager::class],
]
```

Providern hämtar dependencies från containern och skickar dem till konstruktorn.

---

## Dependencies

Dependencies anges som klassnamn eller service-id:

```php
'dependencies' => [
    \Radix\Mailer\MailManager::class,
]
```

Dessa hämtas från containern.

Om dependency inte finns registrerad kommer listener-registreringen att misslyckas.

Se mer i:

- [`SERVICES.md`](SERVICES.md)

---

## Priority

Config kan innehålla:

```php
'priority' => 10
```

Om dispatcher/provider stödjer prioritet kan det styra ordningen.

Om prioritet inte används av aktuell implementation kan värdet ändå finnas kvar som dokumentation/för framtida stöd.

Rekommenderad konvention:

```text
högre priority = viktigare/tidigare
lägre priority = senare
```

---

## Stoppa spridning

En handler kan ange:

```php
'stopPropagation' => true
```

Exempel:

```php
[
    'listener' => \App\EventListeners\LogoutListener::class,
    'type' => 'container',
    'priority' => 20,
    'stopPropagation' => true,
]
```

Om eventet stödjer `stopPropagation()` stoppas efterföljande listeners.

Det är användbart när ett event ska avbryta vidare hantering, till exempel:

- blockerad användare
- säkerhetshändelse
- policy-brott
- hard-fail i ett flöde

---

## Dispatcha event

Använd `EventDispatcher`.

Exempel med dependency injection:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Events\ContactFormEvent;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Response;

final class ContactController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcher $events,
    ) {}

    public function create(): Response
    {
        $this->before();

        // validera formulär...

        $this->events->dispatch(new ContactFormEvent(
            email: 'test@example.com',
            message: 'Hej!',
            firstName: 'Test',
            lastName: 'Person',
        ));

        return redirect(route('home.index'));
    }
}
```

---

## Dispatcha via `app()`

Det går också att hämta dispatchern via containern:

```php
$dispatcher = app(\Radix\EventDispatcher\EventDispatcher::class);

$dispatcher->dispatch(new \App\Events\UserRegisteredEvent(
    email: $email,
    firstName: $firstName,
    activationLink: $link,
));
```

Konstruktorinjektion rekommenderas oftast i controllers/services.

---

## När ska man använda events?

Events passar bra när flera saker ska hända efter en åtgärd eller när sidoeffekter ska frikopplas.

Bra exempel:

```text
skicka mail efter registrering
logga systemhändelse
logga ut blockerad användare
skicka password reset-mail
uppdatera audit log
notifiera admin
```

Mindre bra exempel:

```text
kritisk affärslogik som måste vara tydlig i samma transaktion
kod som alltid behövs direkt för response
logik där ordning och felhantering blir svår att förstå
```

---

## Events i services

Services kan dispatcha events.

Exempel:

```php
final class UserService
{
    public function __construct(
        private readonly \Radix\EventDispatcher\EventDispatcher $events,
    ) {}

    public function register(array $data): User
    {
        $user = new User($data);
        $user->save();

        $this->events->dispatch(new \App\Events\UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            activationLink: $this->activationLink($user),
        ));

        return $user;
    }
}
```

Det håller controllers ännu tunnare.

---

## Events och mail

Vanligt mönster:

```text
UserRegisteredEvent
  -> SendActivationEmailListener

UserPasswordEvent
  -> SendPasswordResetEmailListener

ContactFormEvent
  -> SendContactEmailListener
```

Se mer i:

- [`MAIL.md`](MAIL.md)

---

## Events från scaffolds

Scaffolds kan lägga till:

- events
- listeners
- listener-config
- providers
- mail templates

Exempel:

```bash
php radix scaffold:install auth --force-placeholders
```

Efter scaffold-installation kan du behöva rensa cache:

```bash
php radix cache:clear
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Inbyggda/framework-events

Radix Framework kan också dispatcha interna events, till exempel runt HTTP request/response-livscykeln.

Dessa kan användas för saker som:

- CORS
- security headers
- cache headers
- logging
- response manipulation

Exakt vilka framework-events som finns beror på framework-version.

---

## Felhantering i listeners

Bestäm hur listenerfel ska hanteras.

Exempel:

- mailfel ska kanske loggas men inte stoppa användarregistrering
- säkerhetshändelser ska kanske stoppa vidare hantering
- audit-loggfel ska kanske inte krascha hela requesten

Rekommendation:

```text
kritiska listeners får kasta fel
icke-kritiska listeners loggar och sväljer fel
```

---

## Testning

Testa events och listeners separat.

Exempel på testfall:

```text
ContactFormEvent dispatchas när formulär är giltigt
SendContactEmailListener skickar mail
UserBlockedEvent triggar LogoutListener
stopPropagation stoppar vidare listeners
```

Kör tester:

```bash
composer test
```

---

## Bra praxis

- håll events enkla och datainriktade
- håll listeners fokuserade på en uppgift
- använd services för större logik
- använd konstruktorinjektion där det går
- registrera listeners i config
- använd events för sidoeffekter, inte otydlig kärnlogik
- dokumentera viktiga events
- testa både dispatch och listenerbeteende
- var tydlig med vilka listeners som är kritiska

---

## Felsökning

### Listener körs inte

Kontrollera:

- att eventet dispatchas
- att eventklassens namn matchar config
- att listenern är registrerad
- att provider laddas
- att cache är rensad

Kör:

```bash
php radix cache:clear
```

### Listener kan inte skapas

Kontrollera:

- `type`
- `dependencies`
- att dependencies finns i containern
- att listenerns constructor matchar dependencies

### Fel listener type

Tillåtna typer:

```text
container
custom
```

### Event stoppar inte propagation

Kontrollera:

- att config har `stopPropagation => true`
- att eventklassen stödjer `stopPropagation()`
- att dispatcher respekterar propagation stop

### Listener körs i fel ordning

Kontrollera `priority`.

Om aktuell dispatcher/provider inte sorterar på priority behöver ordningen i config eller provider implementation kontrolleras.

---

## Relaterat

- [`SERVICES.md`](SERVICES.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`MAIL.md`](MAIL.md)
- [`CONFIG.md`](CONFIG.md)
- [`CLI.md`](CLI.md)
- [`TESTING.md`](TESTING.md)
