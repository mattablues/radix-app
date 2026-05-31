# docs/API.md

← [`Tillbaka till index`](INDEX.md)

# API (Radix App)

Radix App har stöd för REST-liknande API:er via routing, API-controllers, JSON responses, middleware, token-validering och CORS.

API-routes ligger normalt under:

```text
/api/v1
```

och definieras i:

```text
routes/api.php
routes/api.user.php
routes/api.admin.php
```

Vilka API-filer som finns beror på installerade scaffolds.

---

## Översikt

Ett API-request går ungefär så här:

```text
Client
  ↓
/api/v1/...
  ↓
API middleware
  ↓
router
  ↓
API controller
  ↓
JsonResponse
```

Vanliga API-delar:

```text
routes/api.php
src/Controllers/Api/
Radix\Controller\AbstractApiController
Radix\Http\JsonResponse
config/cors.php
config/csp.php
config/security.php
```

---

## API routes

API-routes definieras normalt i:

```text
routes/api.php
```

och grupperas under:

```text
/api/v1
```

Exempel:

```php
$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/health', [
        \App\Controllers\Api\HealthController::class,
        'index',
    ])->name('api.health');
});
```

---

## API route-filer från scaffolds

Scaffolds kan lägga till API-route-filer, till exempel:

```text
routes/api.user.php
routes/api.admin.php
```

Dessa kan laddas från `routes/api.php` om filerna finns.

Exempel på endpoints:

```text
/api/v1/health
/api/v1/users
/api/v1/search/users
```

Exakt endpoint-lista beror på installerade scaffolds.

---

## API controllers

API-controllers ligger normalt i:

```text
src/Controllers/Api/
```

Exempel:

```text
src/Controllers/Api/ApiController.php
src/Controllers/Api/HealthController.php
src/Controllers/Api/UserController.php
src/Controllers/Api/SearchController.php
```

Appens API-bascontroller kan ärva från:

```php
Radix\Controller\AbstractApiController
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Controller\AbstractApiController;

abstract class ApiController extends AbstractApiController
{
    protected function now(): int
    {
        return time();
    }
}
```

---

## `AbstractApiController`

Frameworkets API-controller ger bland annat:

```text
json()
getJsonPayload()
validateRequest()
validateRequestAllowingSession()
respondWithErrors()
```

Vanliga användningsområden:

- returnera JSON
- läsa JSON body
- validera payload
- validera API-token
- returnera fel som JSON
- använda statuskoder

---

## JSON response

Returnera JSON med:

```php
return $this->json([
    'success' => true,
    'data' => $data,
]);
```

Med statuskod:

```php
return $this->json([
    'success' => true,
    'data' => $data,
], 201);
```

Det returnerar en `JsonResponse`.

---

## Exempel: API-controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Radix\Http\JsonResponse;

final class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->validateRequest();

        $users = User::all();

        return $this->json([
            'success' => true,
            'data' => $users->map(
                static fn(User $user): array => $user->toArray()
            )->values()->toArray(),
        ]);
    }
}
```

---

## JSON payload

För metoder som skickar body, till exempel `POST`, kan API-controllern läsa JSON body.

Exempel:

```php
$payload = $this->getJsonPayload();
```

`getJsonPayload()` returnerar normalt tom array för:

```text
GET
HEAD
DELETE
```

För ogiltig JSON returneras ett `400 Bad Request`-fel.

---

## Validering av API-request

API-validering ska returnera JSON, inte redirect.

Exempel:

```php
$this->validateRequest([
    'email' => 'required|email',
    'password' => 'required|min:8',
]);
```

Vid valideringsfel returneras normalt:

```text
422 Unprocessable Entity
```

med JSON-body.

Exempel:

```json
{
  "success": false,
  "errors": [
    {
      "field": "email",
      "messages": [
        "Fältet e-post är obligatoriskt."
      ]
    }
  ]
}
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## API-token

`validateRequest()` validerar normalt Bearer-token efter eventuell payload-validering.

Header:

```http
Authorization: Bearer <token>
```

Env:

```dotenv
API_TOKEN=
```

Exempel request:

```bash
curl -H "Authorization: Bearer $API_TOKEN" \
  https://example.com/api/v1/health
```

---

## Session eller token

Vissa endpoints kan acceptera antingen aktiv session eller API-token.

Då kan API-controller använda:

```php
$this->validateRequestAllowingSession();
```

Med regler:

```php
$this->validateRequestAllowingSession([
    'query' => 'required|string|max:100',
]);
```

Det är användbart för endpoints som används både av frontend i inloggad webbsession och externa API-klienter.

---

## Appspecifik token-validering

Frameworkets standardvalidering kan kontrollera `API_TOKEN` från `.env`.

Radix App kan override:a token-validering i sin egen `ApiController`, till exempel för att även stödja tokens i databasen.

Exempel på princip:

```text
1. kontrollera API_TOKEN i .env
2. kontrollera tokens-tabell
3. kontrollera expires_at
4. rensa utgångna tokens
```

---

## Health API

Radix App kan ha health endpoint, till exempel:

```text
GET /api/v1/health
```

Health endpoint kan skyddas med:

```dotenv
HEALTH_REQUIRE_TOKEN=1
```

I development kan tokenkrav vara av beroende på config.

I production rekommenderas:

```dotenv
HEALTH_REQUIRE_TOKEN=1
API_TOKEN=<strong-secret>
```

Health response bör inte läcka för mycket detaljer i production.

Exempel production-svar kan vara begränsat till:

```json
{
  "ok": true,
  "checks": {
    "db": "ok",
    "fs": "ok"
  }
}
```

---

## CORS

CORS konfigureras normalt i:

```text
config/cors.php
```

Vanliga env-värden:

```dotenv
CORS_ALLOW_ORIGIN=http://localhost
CORS_ALLOW_CREDENTIALS=1
```

Rekommendationer:

- tillåt bara origins du behöver
- undvik wildcard i production
- använd inte wildcard med credentials
- begränsa CORS till API-routes

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Preflight / OPTIONS

Browsern skickar `OPTIONS` preflight för vissa CORS requests.

API-routes kan ha en catch-all som returnerar:

```text
204 No Content
```

för OPTIONS.

Exempel:

```php
$router->get('/{any:.*}', function () {
    $response = new \Radix\Http\Response();

    $method = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
        ? $_SERVER['REQUEST_METHOD']
        : '';

    if (strtoupper($method) === 'OPTIONS') {
        $response->setStatusCode(204);
        return $response;
    }

    $response->setStatusCode(404);
    return $response;
})->name('api.preflight');
```

---

## API middleware

Vanliga middleware för API:

```text
request.id
api.logger
security.headers
api.throttle
api.throttle.light
api.throttle.hard
ip.allowlist
```

Exempel:

```php
$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
    ],
], function (\Radix\Routing\Router $router) {
    // API routes
});
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Rate limiting

Publika API-endpoints bör ha rate limiting.

Exempel:

```php
$router->get('/search/users', [
    \App\Controllers\Api\SearchController::class,
    'users',
])->middleware(['api.throttle.light']);
```

För känsliga endpoints:

```php
->middleware(['api.throttle.hard'])
```

Rate limit bör returnera:

```text
429 Too Many Requests
```

---

## Security headers för API

API kan ha egen CSP/security policy.

För API är en strikt policy ofta rimlig:

```text
default-src 'none'
frame-ancestors 'none'
form-action 'none'
```

Security headers sätts normalt av middleware.

---

## Statuskoder

Vanliga API-statuskoder:

```text
200 OK
201 Created
204 No Content

400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
405 Method Not Allowed
422 Unprocessable Entity
429 Too Many Requests

500 Internal Server Error
503 Service Unavailable
```

---

## Felstruktur

Rekommenderad felstruktur:

```json
{
  "success": false,
  "errors": [
    {
      "field": "email",
      "messages": [
        "Fältet e-post är obligatoriskt."
      ]
    }
  ]
}
```

För generella fel:

```json
{
  "success": false,
  "errors": [
    {
      "field": "Request",
      "messages": [
        "Invalid or missing JSON in the request body."
      ]
    }
  ]
}
```

---

## Success-struktur

Rekommenderad success-struktur:

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Anna"
  }
}
```

För listor:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Anna"
    }
  ]
}
```

Med metadata:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 0
  }
}
```

---

## Pagination i API

ORM/query builder kan returnera pagination-data.

Exempel:

```php
$result = User::search(
    term: $term,
    searchColumns: ['first_name', 'last_name', 'email'],
    perPage: 10,
    currentPage: 1
);

return $this->json([
    'success' => true,
    'data' => $result['data'] ?? [],
    'meta' => [
        'current_page' => $result['current_page'] ?? 1,
        'per_page' => $result['per_page'] ?? 10,
        'total' => $result['total'] ?? null,
    ],
]);
```

---

## Cache headers

API-responses som inte ska cachas kan sätta:

```text
Cache-Control: no-store, must-revalidate, max-age=0
Pragma: no-cache
Expires: 0
```

Health endpoints bör ofta inte cachas av browser/proxy om de visar runtime-status.

---

## Logging

API logging bör inte logga:

```text
Authorization
Cookie
tokens
passwords
payloads med känsliga personuppgifter
```

Logga hellre:

```text
request id
method
path
status
duration
IP
user id om relevant
```

Se mer i:

- [`LOGGING.md`](LOGGING.md)

---

## Testning av API

Testa:

- statuskod
- content-type
- JSON-struktur
- auth/token
- validation errors
- invalid JSON
- CORS/preflight
- rate limit
- health response
- production-safe data

Exempeltestfall:

```text
GET /api/v1/health utan token i production -> 401
GET /api/v1/health med token -> 200
POST med ogiltig JSON -> 400
POST med ogiltig payload -> 422
för många requests -> 429
```

Kör:

```bash
composer test
```

---

## Curl-exempel

Health med token:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://example.com/api/v1/health
```

POST JSON:

```bash
curl -X POST https://example.com/api/v1/users \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test"}'
```

Preflight:

```bash
curl -X OPTIONS https://example.com/api/v1/health \
  -H "Origin: https://example.com" \
  -H "Access-Control-Request-Method: GET"
```

---

## Bra praxis

- använd `/api/v1` eller liknande versionsprefix
- returnera alltid JSON från API controllers
- använd tydliga statuskoder
- validera JSON payload
- skydda endpoints med token/session där det behövs
- använd rate limiting på publika endpoints
- konfigurera CORS restriktivt
- logga inte tokens
- dölj detaljerad health-data i production
- testa både success och failure paths

---

## Felsökning

### API returnerar HTML

Kontrollera att routen går till API-controller och returnerar `JsonResponse`.

### 401 Unauthorized

Kontrollera:

```text
Authorization: Bearer <token>
API_TOKEN
token i databas om appen använder DB-tokens
expires_at
```

### 400 Bad Request

Kontrollera att JSON body är giltig.

### 422 Unprocessable Entity

Kontrollera valideringsregler och payload.

### CORS blockeras i browser

Kontrollera:

```text
config/cors.php
CORS_ALLOW_ORIGIN
CORS_ALLOW_CREDENTIALS
```

Kontrollera även att path matchar CORS paths.

### Preflight fungerar inte

Kontrollera att `OPTIONS` hanteras och att CORS listener/middleware är aktiv.

### Health visar för mycket i production

Kontrollera environment:

```text
APP_ENV=production
```

och endpointens production-filter.

---

## Relaterat

- [`ROUTING.md`](ROUTING.md)
- [`HTTP.md`](HTTP.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)
- [`CACHE.md`](CACHE.md)
- [`LOGGING.md`](LOGGING.md)
- [`TESTING.md`](TESTING.md)
