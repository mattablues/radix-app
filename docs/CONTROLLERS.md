# Controllers i Radix

Controllers hanterar inkommande HTTP-förfrågningar och returnerar svar. De fungerar som en brygga mellan dina modeller och vyer.

## Skapa en Controller

Det enklaste sättet att skapa en controller är via CLI:

```bash
php radix make:controller UserController
```

Detta skapar en ny fil i `src/Controllers/`. Om du vill skapa en controller i en undermapp (t.ex. för API), använd:

```bash
php radix make:controller Api/UserController
```

## AbstractController (Web)

Vanliga webb-controllers bör ärva från `Radix\Controller\AbstractController`. Detta ger dig tillgång till vy-rendering, CSRF-skydd och sessionshantering.

### Grundläggande flöde
```php
namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

class HomeController extends AbstractController
{
    public function index(): Response
    {
        // Rendera en vy från views/home.ratio.php
        return $this->view('home', [
            'title' => 'Välkommen till Radix'
        ]);
    }
}
```

### Inbyggt CSRF-skydd
Genom att anropa `$this->before()` i dina POST/PUT/DELETE-metoder valideras CSRF-token automatiskt:

```php
public function store(): Response
{
    $this->before(); // Validerar CSRF automatiskt
    // Fortsätt med att spara data...
}
```

## ApiController (JSON)

För API:er bör du istället ärva från `Radix\Controller\ApiController`. Denna klass är optimerad för JSON-kommunikation.

- **`$this->json($data)`**: Returnerar ett korrekt formaterat JSON-svar.
- **`$this->validateRequest($rules)`**: Validerar inkommande JSON-data och skickar automatiskt `422` vid fel.
- **`getJsonPayload()`**: Hämtar och dekodar JSON från förfrågans body.

## Dependency Injection

Radix stöder autowiring i controllerns konstruktor. Du kan injicera tjänster som behövs direkt:

```php
public function __construct(
    private MailManager $mail,
    private EventDispatcher $events
) {}
```

## Hjälpmetoder i Controllers

- **`$this->request`**: Tillgång till Request-objektet (GET, POST, Session, Headers).
- **`$this->viewer`**: Tillgång till vy-systemet.
- **`filters()`**: Du kan definiera en `filters()`-metod för att registrera anpassade template-filter som bara gäller för denna controller.