# docs/SERVICES.md

← [`Tillbaka till index`](INDEX.md)

# Services & Dependency Injection (Radix App)

Radix App använder en Dependency Injection-container (DI) för att hantera objekt och deras beroenden.  
Det gör koden mer testbar, mer modulär och enklare att underhålla.

---

## Översikt

I Radix App registrerar du tjänster främst via:

- **Service Providers** (`src/Providers/` + `config/providers.php`)
- **Container-boot** i `config/services.php` (för wire-up av core services och bindingar)

I praktiken:

- appen “bootar” containern
- config laddas
- providers registrerar/konfigurerar tjänster
- controllers/services/listeners får dependencies via konstruktorinjektion

---

## Service Providers

Service Providers är centrala platser för att registrera tjänster i containern.

- Providers ligger i `src/Providers/`
- Aktiveras via `config/providers.php`

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Radix\ServiceProvider\ServiceProviderInterface;

final class MyServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $container = app();

        $container->addShared(\App\Services\MyService::class, function () {
            return new \App\Services\MyService('config-value');
        });
    }
}
```

> Rekommendation: lägg “app-nära” wiring i providers så att `config/services.php` inte blir en monolit.

---

## `config/services.php` (container-boot)

För tjänster som kräver specifik wiring, paths eller setup kan de registreras i `config/services.php`.

Typiska exempel:
- konfigurationsobjekt (`Config`)
- cache/logging
- db-connection
- migrator/seed runner
- viewer/template engine
- commands registry

---

## Använda containern

### 1) Autowiring (rekommenderat)

Det föredragna sättet är konstruktorinjektion i controllers/services/listeners:

```php
public function __construct(
    private readonly \App\Services\AuthService $authService,
) {}
```

### 2) Helpern `app()` (service locator)

Du kan hämta tjänster manuellt (mindre idealiskt, men ibland praktiskt):

```php
$mailer = app(\Radix\Mailer\MailManager::class);
```

---

## Vanliga helpers

Radix brukar ha helpers som interagerar med containern och appens tjänster, t.ex.:

- `app()` — hämtar containern eller en specifik tjänst
- `request()` — hämtar nuvarande request (om tillgängligt)
- `view()` — renderar en vy via viewer
- `config()` — hämtar värden från sammanslagen konfiguration

---

## Miljövariabler (.env)

Känslig och miljöspecifik konfiguration ligger i `.env` och nås via `getenv()` (eller via config-lager om du mappar env => config).

Bra tumregel:
- secrets i `.env`
- defaults och struktur i `config/`

Se även:

- [`docs/CONFIG.md`](CONFIG.md)
