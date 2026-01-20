# E-posthantering (Mail)

Radix erbjuder ett smidigt sätt att skicka e-post genom `MailManager`. Systemet stödjer både enkla textmeddelanden och rika HTML-mejl som renderas via vyer.

## Konfiguration

Inställningar för e-post hittas i din `.env`-fil. Radix använder vanligtvis SMTP för att skicka mejl.

```bash
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Radix App"
```

Ytterligare detaljer finns i `config/email.php`.

## Skicka e-post

Du kan injicera `Radix\Mailer\MailManager` i din klass för att skicka mejl.

### Enkelt exempel
```php
public function __construct(private MailManager $mailManager) {}

public function sendWelcome() {
    $this->mailManager->send(
        'user@example.com',
        'Välkommen!',
        'Tack för att du valde vår tjänst.'
    );
}
```

### Använda Templates (HTML)
För att skicka snygga HTML-mejl kan du skicka med en array med template-information som det fjärde argumentet:

```php
$this->mailManager->send(
    'user@example.com',
    'Aktivera konto',
    '', // Tomt för text-body om template används
    [
        'template' => 'emails.activate', // Hittas i views/emails/activate.ratio.php
        'data' => [
            'firstName' => 'Anna',
            'url' => 'https://example.com/activate/token'
        ],
        'reply_to' => 'support@example.com'
    ]
);
```

## E-post via Events

Det rekommenderade sättet att skicka mejl i Radix är via **Events**. Istället för att skicka mejlet direkt i en Controller, triggar du en händelse:

1.  **Event**: `UserRegisteredEvent` triggas i din Controller.
2.  **Listener**: `SendActivationEmailListener` fångar händelsen.
3.  **Mail**: Lyssnaren använder `MailManager` för att skicka mejlet.

Detta håller din kod ren och gör att e-postutskicket sker "bakom kulisserna".

## Felsökning

- **Loggar**: Om e-postmisslyckas, kontrollera `storage/logs/` för SMTP-felmeddelanden.
- **Portar**: Se till att din server tillåter utgående trafik på port 587 (TLS) eller 465 (SSL).
- **Templates**: Om mejlet ser konstigt ut, kontrollera att din `.ratio.php` fil i `views/emails/` är korrekt formaterad.