# API-utveckling i Radix

Radix har inbyggt stöd för att bygga RESTful API:er. Systemet särskiljer automatiskt på API-anrop och vanliga webbanrop baserat på URL-strukturen `/api/v{version}/`.

## ApiController

När du bygger API:er bör dina controllers ärva från `Radix\Controller\ApiController`. Denna basklass tillhandahåller hjälpfunktioner för JSON-svar och API-specifik validering.

### Exempel på Controller
```php
namespace App\Controllers\Api;

use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;
use App\Models\User;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        // validateRequest kontrollerar token/session automatiskt
        $this->validateRequest();

        $users = User::all();

        // returnerar automatiskt ett JsonResponse med rätt headers
        return $this->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
```

## JSON-svar och Felhantering

Använd `$this->json($data, $statusCode)` för att skicka svar. Vid valideringsfel eller andra problem kan du använda `respondWithErrors()`:

```php
if (!$user) {
    return $this->respondWithErrors(['user' => 'Hittades inte'], 404);
}
```

## Validering

API-validering skiljer sig från webbvalidering då den inte omdirigerar användaren vid fel, utan returnerar ett `422 Unprocessable Entity` svar med felmeddelandena i JSON-format.

```php
$this->validateRequest([
    'email' => 'required|email',
    'password' => 'required|min:8'
]);
```

## Routing för API

API-rutter bör grupperas i `routes/api.php` med rätt prefix och middleware.

```php
$router->group(['path' => '/api/v1', 'middleware' => ['api.throttle', 'api.auth']], function($router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
});
```

## Säkerhet och CORS

- **Rate Limiting**: Använd `api.throttle` middleware för att begränsa antal anrop.
- **CORS**: Inställningar för vilka domäner som får anropa ditt API finns i `config/cors.php`.
- **CSRF**: API-anrop (under `/api/v...`) är undantagna från standard CSRF-validering för att underlätta för externa klienter, men bör istället skyddas med tokens eller session-auth.