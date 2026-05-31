# docs/MAIL.md

← [`Tillbaka till index`](INDEX.md)

# E-post (Radix App)

Radix App kan skicka e-post via **Radix Frameworks** mail-abstraktion:

```php
Radix\Mailer\MailManager
Radix\Mailer\MailerInterface
```

I appen används normalt en konkret mailer, till exempel:

```php
App\Mail\PHPMailerMailer
```

Den använder PHPMailer och kan skicka både text och HTML-mail.

---

## Översikt

Mailflödet ser ungefär ut så här:

```text
Controller/service
  ↓
dispatchar event eller anropar mail service
  ↓
listener/service använder MailManager
  ↓
MailManager använder appens MailerInterface-implementation
  ↓
PHPMailer skickar via SMTP
```

Rekommenderat är ofta att skicka mail via events/listeners.

---

## Viktiga klasser

Framework:

```php
Radix\Mailer\MailManager
Radix\Mailer\MailerInterface
```

App:

```php
App\Mail\PHPMailerMailer
```

Templates:

```text
views/emails/
views/layouts/email.ratio.php
```

---

## Konfiguration

Mail styrs via:

```text
.env
config/mail.php
config/email.php
```

`config/mail.php` anger normalt vilken mailer-klass som används.

Exempel:

```php
<?php

declare(strict_types=1);

return [
    'mail' => [
        'mailer_class' => \App\Mail\PHPMailerMailer::class,
    ],
];
```

SMTP-inställningar ligger normalt i `.env` och mappas via `config/email.php`.

---

## SMTP-inställningar

Vanliga `.env`-nycklar:

```dotenv
MAIL_DEBUG=0
MAIL_CHARSET=UTF-8
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_SECURE=tls
MAIL_AUTH=0
MAIL_ACCOUNT=
MAIL_PASSWORD=
MAIL_EMAIL=noreply@example.com
MAIL_FROM="Radix System"
```

### `MAIL_DEBUG`

Slå på SMTP-debug i development:

```dotenv
MAIL_DEBUG=1
```

Ha normalt av i production:

```dotenv
MAIL_DEBUG=0
```

### `MAIL_SECURE`

Vanliga värden:

```text
tls
ssl
none
```

### `MAIL_AUTH`

Sätt till `1` om SMTP-servern kräver autentisering:

```dotenv
MAIL_AUTH=1
```

---

## MailManager

`MailManager` är den service som appkod normalt använder.

Exempel:

```php
$mailManager->send(
    to: 'user@example.com',
    subject: 'Välkommen!',
    body: 'Tack för att du registrerade dig.'
);
```

Signaturmässigt används:

```php
send(string $to, string $subject, string $body, array $options = []): bool
```

Returnerar:

```text
true  = skickat
false = misslyckades
```

---

## Skicka enkel text

```php
<?php

declare(strict_types=1);

use Radix\Mailer\MailManager;

final readonly class WelcomeMailer
{
    public function __construct(
        private MailManager $mailManager,
    ) {}

    public function sendWelcome(string $to): bool
    {
        return $this->mailManager->send(
            $to,
            'Välkommen!',
            'Tack för att du valde vår tjänst.',
            [
                'is_html' => false,
            ]
        );
    }
}
```

---

## Skicka HTML-mail

Som default kan appens PHPMailer-mailer skicka HTML.

```php
$this->mailManager->send(
    'user@example.com',
    'Välkommen!',
    '<h1>Välkommen!</h1><p>Tack för att du registrerade dig.</p>',
    [
        'is_html' => true,
    ]
);
```

Använd inte osanerad användarinput direkt i HTML-body.

---

## Skicka mail via template

Email-templates ligger normalt i:

```text
views/emails/
```

Exempel:

```text
views/emails/activate.ratio.php
views/emails/contact.ratio.php
views/emails/password-reset.ratio.php
```

Skicka med template:

```php
$this->mailManager->send(
    'user@example.com',
    'Aktivera konto',
    '',
    [
        'template' => 'emails.activate',
        'data' => [
            'firstName' => 'Anna',
            'url' => 'https://example.com/activate/token',
        ],
    ]
);
```

Mailern renderar då templaten via template viewer.

---

## Email-layout

Email templates kan använda en layout, till exempel:

```text
views/layouts/email.ratio.php
```

Exempel:

```html
{% extends "layouts/email.ratio.php" %}

{% block body %}
    <p>Hej {{ $firstName }}!</p>

    <p>
        <a href="{{ $url }}">Aktivera ditt konto</a>
    </p>
{% endblock %}
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Options

`send()` kan ta options.

Vanliga options:

```text
from
from_name
reply_to
is_html
template
data
```

Exempel:

```php
$this->mailManager->send(
    'user@example.com',
    'Kontakt',
    '',
    [
        'template' => 'emails.contact',
        'data' => [
            'message' => 'Hej!',
        ],
        'reply_to' => 'sender@example.com',
        'from_name' => 'Radix App',
        'is_html' => true,
    ]
);
```

---

## `from`

Ange avsändaradress:

```php
[
    'from' => 'noreply@example.com',
]
```

Om `from` saknas används default från mailkonfigurationen.

Ogiltig `from` stoppas.

---

## `from_name`

Ange avsändarnamn:

```php
[
    'from_name' => 'Radix App',
]
```

Om `from_name` saknas används default från mailkonfigurationen.

---

## `reply_to`

Ange Reply-To:

```php
[
    'reply_to' => 'support@example.com',
]
```

Det är särskilt användbart för kontaktformulär.

---

## `is_html`

Ange om body är HTML:

```php
[
    'is_html' => true,
]
```

För textmail:

```php
[
    'is_html' => false,
]
```

---

## `template`

Ange template:

```php
[
    'template' => 'emails.activate',
]
```

Template renderas via appens template viewer.

---

## `data`

Data som skickas till templaten:

```php
[
    'data' => [
        'firstName' => 'Anna',
        'url' => $activationUrl,
    ],
]
```

---

## Rekommenderat: mail via events

För att hålla controllers rena är det ofta bäst att:

1. utföra domänlogik i controller/service
2. dispatcha ett event
3. låta en listener skicka mail

Exempel:

```text
UserRegisteredEvent
  ↓
SendActivationEmailListener
  ↓
MailManager::send(...)
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Exempel: event + listener

Event:

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

Listener:

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
            'Aktivera konto',
            '',
            [
                'template' => 'emails.activate',
                'data' => [
                    'firstName' => $event->firstName,
                    'url' => $event->activationLink,
                ],
            ]
        );
    }
}
```

---

## Registrera mail-listener

Exempel i listener-config:

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
];
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## MailServiceProvider

Appen kan ha en provider som registrerar mail services, till exempel:

```text
src/Providers/MailServiceProvider.php
```

Den kopplar normalt ihop:

```text
MailManager
MailerInterface implementation
TemplateViewer
Config
```

Se mer i:

- [`SERVICES.md`](SERVICES.md)

---

## Kontaktformulär

Ett vanligt mönster:

```text
ContactController
  validerar ContactRequest
  dispatchar ContactFormEvent

SendContactEmailListener
  skickar emails.contact-template
```

Fördel:

- controller hålls ren
- mail-logiken testas separat
- fler listeners kan läggas till senare

---

## Password reset

Ett vanligt auth-flöde:

```text
UserPasswordEvent
  ↓
SendPasswordResetEmailListener
  ↓
emails.password-reset
```

---

## Kontoaktivering

Ett vanligt registreringsflöde:

```text
UserRegisteredEvent
  ↓
SendActivationEmailListener
  ↓
emails.activate
```

---

## Säkerhet

### Logga inte mail secrets

Logga aldrig:

```text
MAIL_PASSWORD
SMTP credentials
tokens
reset links i onödan
activation links i onödan
```

### Validera mottagare

Kontrollera att e-postadresser är giltiga innan du skickar.

### Sanera HTML

Om mail innehåller användarinput ska den escapas i template:

```html
{{ $message }}
```

Använd inte:

```html
{{ $message|raw }}
```

för osäker input.

### Tokens i länkar

Activation/password reset-länkar innehåller ofta tokens.

Tänk på:

- kort livslängd
- en-gångs-användning
- HTTPS i production
- logga inte token
- visa inte token i felmeddelanden

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Development

För lokal utveckling är Mailtrap eller liknande ofta bra.

Exempel:

```dotenv
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_SECURE=tls
MAIL_AUTH=1
MAIL_ACCOUNT=...
MAIL_PASSWORD=...
MAIL_EMAIL=noreply@example.com
MAIL_FROM="Radix Dev"
```

---

## Production

I production:

```dotenv
MAIL_DEBUG=0
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SECURE=tls
MAIL_AUTH=1
MAIL_ACCOUNT=...
MAIL_PASSWORD=...
MAIL_EMAIL=noreply@example.com
MAIL_FROM="Radix App"
```

Rekommendation:

- använd riktig SMTP-provider
- använd SPF/DKIM/DMARC
- håll credentials i `.env`
- logga fel utan att exponera credentials
- använd HTTPS-länkar

---

## Felsökning

### Mail skickas inte

Kontrollera:

```text
MAIL_HOST
MAIL_PORT
MAIL_SECURE
MAIL_AUTH
MAIL_ACCOUNT
MAIL_PASSWORD
MAIL_EMAIL
MAIL_FROM
```

Kontrollera även att servern tillåter utgående trafik på SMTP-porten.

Vanliga portar:

```text
587 TLS
465 SSL
25 SMTP
2525 Mailtrap/dev
```

### From-adress är ogiltig

Kontrollera:

```text
MAIL_EMAIL
```

Den måste vara en giltig e-postadress.

### SMTP auth failar

Kontrollera:

```text
MAIL_AUTH
MAIL_ACCOUNT
MAIL_PASSWORD
```

### HTML-mail ser fel ut

Kontrollera:

- email template
- email layout
- data som skickas till template
- att `is_html` är true

### Template hittas inte

Kontrollera att template-namnet:

```text
emails.activate
```

matchar filen:

```text
views/emails/activate.ratio.php
```

Rensa cache:

```bash
php radix cache:clear
```

### Debug saknas

I development:

```dotenv
MAIL_DEBUG=1
```

I production ska debug normalt vara av.

### Listener skickar inte mail

Kontrollera:

- att event dispatchas
- att listener är registrerad
- att MailManager finns i containern
- att dependencies i listener-config matchar constructorn
- att cache är rensad

Se:

- [`EVENTS.md`](EVENTS.md)

---

## Testning

För tester kan du:

- mocka `MailManager`
- använda fake mailer som implementerar `MailerInterface`
- testa listenern separat
- verifiera att rätt template/data skickas

Exempel på testfall:

```text
SendActivationEmailListener skickar emails.activate
SendPasswordResetEmailListener skickar emails.password-reset
SendContactEmailListener använder reply_to
ogiltig from-adress nekas
```

Kör:

```bash
composer test
```

---

## Bra praxis

- skicka mail via events/listeners
- håll controllers fria från maildetaljer
- använd templates för HTML-mail
- escapa användarinput i mailtemplates
- logga inte mail credentials eller tokens
- använd separata mailflöden för activation/reset/contact
- använd SMTP-provider i production
- använd `MAIL_DEBUG=0` i production
- testa listeners separat

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`EVENTS.md`](EVENTS.md)
- [`SERVICES.md`](SERVICES.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`SECURITY.md`](SECURITY.md)
- [`LOGGING.md`](LOGGING.md)
- [`TESTING.md`](TESTING.md)
