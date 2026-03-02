# docs/MAIL.md

← [`Tillbaka till index`](INDEX.md)

# E-post (Mail) (Radix App)

Radix kan skicka e-post via `Radix\Mailer\MailManager`.  
Systemet stödjer både enkla textmeddelanden och HTML-mejl renderade via templates.

---

## Konfiguration

Mail styrs via `.env` + ev. configfil (beroende på din setup).

Typiska SMTP-variabler:

```dotenv
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_SECURE=tls
MAIL_AUTH=1
MAIL_ACCOUNT=
MAIL_PASSWORD=
MAIL_EMAIL=noreply@example.com
MAIL_FROM="Radix App"
```

> Tips: slå på mail-debug i development om du vill se mer information vid sändning.

---

## Skicka e-post

Du kan injicera `Radix\Mailer\MailManager` i t.ex. en service eller listener.

### Enkelt exempel (text)

```php
<?php

use Radix\Mailer\MailManager;

final readonly class WelcomeMailer
{
    public function __construct(private MailManager $mailManager) {}

    public function sendWelcome(string $to): void
    {
        $this->mailManager->send(
            $to,
            'Välkommen!',
            'Tack för att du valde vår tjänst.'
        );
    }
}
```

---

## Skicka HTML-mail via templates

Du kan skicka HTML-mejl genom att referera till en template (t.ex. `views/emails/activate.ratio.php`) och skicka data.

Exempel:

```php
<?php

$this->mailManager->send(
    'user@example.com',
    'Aktivera konto',
    '', // text-body kan vara tomt om template används
    [
        'template' => 'emails.activate',
        'data' => [
            'firstName' => 'Anna',
            'url' => 'https://example.com/activate/token'
        ],
        'reply_to' => 'support@example.com'
    ]
);
```

---

## Rekommenderat: skicka mail via events

För att hålla controllers rena är det ofta bättre att:

1) dispatcha en event (t.ex. `UserRegisteredEvent`)
2) låta en listener skicka mejlet

Då slipper du mail-logik i controllers och kan enklare testa/utveckla vidare.

Se:

- [`docs/EVENTS.md`](EVENTS.md)

---

## Felsökning

- Kontrollera loggar om mail misslyckas (se `docs/LOGGING.md`)
- Kontrollera att din server tillåter utgående trafik på rätt port (t.ex. 587 TLS / 465 SSL)
- Om HTML-mejl ser fel ut: kontrollera att din `.ratio.php` template är korrekt och att data skickas in som du tänker
