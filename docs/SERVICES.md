# Services och Dependency Injection

Radix använder en kraftfull Dependency Injection (DI) container för att hantera objekt och deras beroenden. Detta gör koden testbar och modulär.

## Service Providers

Service Providers är centrala platser för att registrera tjänster i containern. De definieras i `src/Providers/` och aktiveras i `config/providers.php`.

```php
namespace App\Providers;

use Radix\ServiceProvider\ServiceProviderInterface;

readonly class MyServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $container = app();
        $container->set(MyService::class, fn() => new MyService('config-value'));
    }
}
```

## Konfiguration (`config/services.php`)

För enklare tjänster eller tjänster som kräver specifik konfiguration kan du använda `config/services.php`. Här kan du definiera hur klasser ska instansieras.

## Använda containern

### Via Autowiring
Det rekommenderade sättet är att låta Radix injicera beroenden automatiskt via konstruktorn i Controllers, Services eller Listeners:

```php
public function __construct(
    private MyService $service,
    private Database $db
) {}
```

### Via Helper-funktionen `app()`
Du kan även hämta tjänster manuellt (Service Location), även om konstruktor-injektion föredras:

```php
$mailer = app(\Radix\Mailer\MailManager::class);
```

## Globala Helpers
Radix tillhandahåller flera helpers som interagerar med containern:
- `app()` - Hämtar containern eller en specifik tjänst.
- `request()` - Hämtar den nuvarande HTTP-requesten.
- `view()` - Renderar en vy via `RadixTemplateViewer`.
- `config()` - Hämtar värden från konfigurationsfilerna.

## Miljövariabler (.env)
Känslig konfiguration eller miljöspecifika inställningar lagras i `.env` och nås via `getenv()` eller `config()`-helpers.