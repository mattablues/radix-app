# docs/EVENTS.md

← [`Tillbaka till index`](INDEX.md)

# Events & listeners (Radix App)

Radix använder en `EventDispatcher` för att implementera ett Observer-liknande mönster.  
Det gör att du kan reagera på händelser (t.ex. “user registrerad”, “user blocked”) utan att blanda in logiken i controllers.

---

## Översikt

- **Event** = en enkel klass som bär data
- **Listener** = en klass som hanterar eventet (oftast via `__invoke`)
- **Registrering** = i appens listener-konfig (vanligtvis `config/listeners.php`)
- **Dispatch** = via `EventDispatcher`

---

## 1) Skapa en event

Events placeras typiskt i:

- `src/Events/`

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Radix\EventDispatcher\Event;

final class UserRegisteredEvent extends Event
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $activationLink,
    ) {}
}
```

---

## 2) Skapa en listener

Listeners placeras typiskt i:

- `src/EventListeners/`

En listener är ofta en klass med en `__invoke(...)`-metod:

```php
<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\UserRegisteredEvent;
use Radix\Mailer\MailManager;

final readonly class SendActivationEmailListener
{
    public function __construct(private MailManager $mailManager) {}

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

## 3) Registrera listeners

Listeners registreras i appens konfiguration (vanligtvis `config/listeners.php`).

Radix stödjer typiskt två sätt att instansiera listeners:

- **container**: hämta lyssnaren direkt från DI-containern
- **custom**: skapa lyssnaren manuellt med specificerade dependencies

Exempel:

```php
<?php

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

## 4) Dispatcha en event

Du dispatchar en event via `EventDispatcher`:

```php
<?php

use App\Events\UserRegisteredEvent;

$dispatcher = app(\Radix\EventDispatcher\EventDispatcher::class);

$event = new UserRegisteredEvent($email, $firstName, $link);
$dispatcher->dispatch($event);
```

---

## Stoppa spridning (stop propagation)

Om du vill förhindra att efterföljande listeners för samma event körs kan du (om din setup stödjer det) sätta:

- `'stopPropagation' => true`

Det kan vara användbart vid t.ex. säkerhetshändelser där ett blockerat konto ska avbryta vidare flöden.

---

## Inbyggda events

Ramverket kan även dispatcha interna events (t.ex. events runt response/request) som används för att t.ex. lägga headers (CORS, cache-control, security headers).

I appen kan du koppla på egna listeners på samma sätt om du vill hooka in logik runt request/response-livscykeln.
