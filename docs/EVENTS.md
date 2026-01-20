# Events och Listeners i Radix

Radix använder en `EventDispatcher` för att implementera "Observer"-mönstret. Detta gör det möjligt att reagera på händelser i systemet (t.ex. att en användare har registrerat sig) utan att blanda ihop logiken i controllern.

## 1. Skapa en Event

En event är en enkel klass som bär på den data som behövs. De placeras vanligtvis i `src/Events/`.

```php
namespace App\Events;

use Radix\EventDispatcher\Event;

final class UserRegisteredEvent extends Event
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $activationLink
    ) {}
}
```

## 2. Skapa en Listener

En listener är en klass som utför en handling när en event triggas. Den bör ha en `__invoke`-metod. De placeras i `src/EventListeners/`.

```php
namespace App\EventListeners;

use App\Events\UserRegisteredEvent;
use Radix\Mailer\MailManager;

readonly class SendActivationEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->mailManager->send($event->email, 'Välkommen!', "Klicka här: {$event->activationLink}");
    }
}
```

## 3. Registrera Listeners

Alla lyssnare registreras i `config/listeners.php`. Radix stödjer två sätt att instansiera lyssnare via `ListenersServiceProvider`:

1.  **container**: Hämtar lyssnaren direkt från DI-containern.
2.  **custom**: Instansierar lyssnaren manuellt med specifika beroenden från containern.

**Exempel (`config/listeners.php`):**
```php
return [
    \App\Events\UserRegisteredEvent::class => [
        [
            'listener' => \App\EventListeners\SendActivationEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
            'priority' => 10,
        ],
    ],
    \App\Events\UserBlockedEvent::class => [
        [
            'listener' => \App\EventListeners\LogoutListener::class,
            'type' => 'container',
            'stopPropagation' => true,
        ],
    ],
];
```

## 4. Trigga en Event

För att skicka iväg en händelse använder du `EventDispatcher`.

```php
// I en controller eller service
$dispatcher = app(\Radix\EventDispatcher\EventDispatcher::class);

$event = new UserRegisteredEvent($email, $firstName, $link);
$dispatcher->dispatch($event);
```

## Stoppa spridning (Stop Propagation)

Om du vill förhindra att efterföljande lyssnare för samma händelse körs, kan du sätta `'stopPropagation' => true` i konfigurationen (förutsatt att event-klassen är "stoppable"). Detta är användbart vid t.ex. säkerhetshändelser där ett blockerat konto ska avbryta alla andra processer.

## Inbyggda händelser

Radix skickar även ut inbyggda händelser, som t.ex. `ResponseEvent` precis innan ett svar skickas till webbläsaren. Detta används av systemet för att automatiskt lägga till headers för t.ex. `Cache-Control` och `CORS`.