# docs/API.md

← [`Tillbaka till index`](INDEX.md)

# API (Radix App)

Radix har stöd för att bygga REST-liknande API:er. Ofta särskiljs API från web genom URL-struktur (t.ex. `/api/v1/...`), och API-svar returneras som JSON.

---

## ApiController (JSON)

När du bygger API:er kan dina controllers ärva från en API-basklass (t.ex. `Radix\Controller\ApiController` i din setup).

Den brukar ge:

- helpers för JSON-svar (t.ex. `$this->json(...)`)
- API-anpassad validering (returnerar JSON vid fel, inte redirects)
- konsekventa statuskoder

---

## Exempel: API-controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

final class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        // Om din setup har API-validering/auth-helper, kör den här
        $this->validateRequest();

        $users = User::all();

        return $this->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
```

---

## JSON-svar och fel

Skicka svar med tydlig statuskod.

Exempel (404):

```php
<?php

if (!$user) {
    return $this->respondWithErrors(['user' => 'Hittades inte'], 404);
}
```

---

## Validering (API)

API-validering ska normalt returnera `422 Unprocessable Entity` med fel i JSON-format (i stället för att redirecta tillbaka).

Exempel:

```php
<?php

$this->validateRequest([
    'email' => 'required|email',
    'password' => 'required|min:8',
]);
```

Se även:

- [`docs/VALIDATION.md`](VALIDATION.md)

---

## Routing för API

API-rutter grupperas ofta i `routes/api.php` med version-prefix och middleware.

```php
<?php

use App\Controllers\Api\UserController;

$router->group(['path' => '/api/v1', 'middleware' => ['api.throttle', 'api.auth']], function ($router) {
    $router->get('/users', [UserController::class, 'index']);
    $router->post('/users', [UserController::class, 'store']);
});
```

---

## Säkerhet (CORS, rate limiting, auth)

Rekommendationer:

- **Rate limiting:** sätt throttling på publika endpoints
- **CORS:** tillåt bara origins du behöver
- **Auth:** skydda API med token eller session-baserad auth (beroende på use case)
- **CSRF:** API-endpoints brukar inte använda samma CSRF-flöde som web, men måste skyddas på annat sätt

Se även:

- [`docs/SECURITY.md`](SECURITY.md)
- [`docs/HTTP.md`](HTTP.md)
