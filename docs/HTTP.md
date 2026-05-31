# docs/HTTP.md

← [`Tillbaka till index`](INDEX.md)

# HTTP (Radix App)

Den här guiden beskriver HTTP-lagret i Radix App:

- request
- response
- JSON responses
- redirects
- headers
- sessions
- statuskoder
- testning av endpoints

Radix App använder HTTP-klasser från **Radix Framework**, framför allt under namespace:

```php
Radix\Http
```

---

## Översikt

De viktigaste HTTP-delarna är:

```text
Request
Response
JsonResponse
RedirectResponse
FormRequest
RequestHandler
```

Vanliga klasser:

```php
Radix\Http\Request
Radix\Http\Response
Radix\Http\JsonResponse
Radix\Http\RedirectResponse
Radix\Http\FormRequest
```

---

## Request-cykel

Ett vanligt web request går ungefär så här:

```text
Browser
  ↓
public/index.php
  ↓
Request skapas
  ↓
middleware
  ↓
router
  ↓
controller / handler
  ↓
Response
  ↓
send()
  ↓
Browser
```

---

## Request

`Request` representerar inkommande HTTP-anrop.

Den innehåller bland annat:

```text
uri
method
get
post
files
cookie
server
session
```

Exempel på sådant du ofta vill läsa:

```text
HTTP method
path/URI
query params
POST-data
uploaded files
cookies
headers
client IP
session
CSRF-token
```

---

## Skapa request från globals

I normal drift skapas requesten från PHP:s superglobals:

```php
$request = \Radix\Http\Request::createFromGlobals();
```

Den läser från:

```text
$_SERVER
$_GET
$_POST
$_FILES
$_COOKIE
```

URI normaliseras bland annat genom att:

- query string tas bort
- `/index.php` i början tas bort i vissa Apache-miljöer
- URI säkerställs börja med `/`

---

## Request properties

`Request` har publika properties för grunddata.

Exempel:

```php
$uri = $request->uri;
$method = $request->method;

$query = $request->get;
$post = $request->post;
$files = $request->files;
$cookies = $request->cookie;
$server = $request->server;
```

---

## HTTP method

Metoden finns på requesten:

```php
$method = strtoupper($request->method);
```

Exempel:

```php
if (strtoupper($request->method) === 'POST') {
    // hantera POST
}
```

Vanliga HTTP-metoder:

```text
GET
POST
PUT
PATCH
DELETE
OPTIONS
HEAD
```

Vilka metoder som används i routes beror på router och appens route-definitioner.

---

## URI och full URL

Requestens path/URI:

```php
$uri = $request->uri;
```

Full URL:

```php
$url = $request->fullUrl();
```

Exempelresultat:

```text
https://example.com/contact?from=footer
```

---

## Query och POST-data

Query params finns i:

```php
$request->get
```

POST-data finns i:

```php
$request->post
```

Exempel:

```php
$email = isset($request->post['email']) && is_string($request->post['email'])
    ? $request->post['email']
    : '';
```

För mer strukturerad validering rekommenderas form requests eller egna request-klasser.

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Uploaded files

Uppladdade filer finns i:

```php
$request->files
```

Exempel:

```php
$file = $request->files['avatar'] ?? null;
```

Validera alltid uppladdade filer noggrant:

- storlek
- MIME/content type
- filändelse
- bilddimensioner om relevant
- säker lagringsplats

Se mer i:

- [`FILES.md`](FILES.md)
- [`IMAGES.md`](IMAGES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Headers

Läs en header med:

```php
$authorization = $request->header('Authorization');
```

Med default-värde:

```php
$contentType = $request->header('Content-Type', 'text/plain');
```

Exempel:

```php
$token = $request->header('Authorization');

if ($token === null) {
    // saknar Authorization-header
}
```

---

## Client IP

Hämta klientens IP:

```php
$ip = $request->ip();
```

Om appen kör bakom reverse proxy eller load balancer behöver trusted proxy-konfigurationen vara korrekt, annars kan IP-baserad logik bli fel.

Exempel på IP-baserad logik:

- rate limiting
- IP allowlist
- logging
- security events
- geolocation

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`GEOLOCATION.md`](GEOLOCATION.md)

---

## CSRF-token

Request kan läsa CSRF-token från POST-data:

```php
$token = $request->getCsrfToken();
```

Token hämtas normalt från:

```text
csrf_token
```

Exempel i formulär:

```php
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Session

Om session är initialiserad kan den nås via:

```php
$session = $request->session();
```

Exempel:

```php
$request->session()->set('flash', [
    'type' => 'success',
    'message' => 'Sparat!',
]);
```

Om session inte är initierad och du anropar `session()` kastas ett fel.

Sessions används bland annat för:

- auth
- flash messages
- old input
- CSRF-token
- user state

Se mer i:

- [`CONFIG.md`](CONFIG.md)
- [`SECURITY.md`](SECURITY.md)

---

## Viewer från request

Request kan ge tillgång till template viewer:

```php
$viewer = $request->viewer();
```

I controllers används ofta enklare helpers, till exempel:

```php
return $this->view('home.index');
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## Filtrera formulärfält

Request har hjälp för att filtrera bort fält som inte ska användas som vanlig input.

Exempel:

```php
$data = $request->filterFields($request->post);
```

Default exkluderas typiskt:

```text
csrf_token
password_confirmation
honeypot
```

Du kan ange egna fält:

```php
$data = $request->filterFields($request->post, [
    'csrf_token',
    'honeypot',
    'submit',
]);
```

---

## Response

`Response` representerar HTTP-svaret som skickas tillbaka.

Den innehåller:

```text
status code
headers
body
```

Skapa en response:

```php
$response = new \Radix\Http\Response();
$response->setStatusCode(200);
$response->setHeader('Content-Type', 'text/plain; charset=utf-8');
$response->setBody('Hej!');

return $response;
```

---

## Response body

Sätt body:

```php
$response->setBody('Hello world');
```

Läs body:

```php
$body = $response->getBody();
```

eller:

```php
$body = $response->body();
```

---

## Response status

Sätt statuskod:

```php
$response->setStatusCode(404);
```

Läs statuskod:

```php
$status = $response->getStatusCode();
```

---

## Response headers

Sätt header:

```php
$response->setHeader('Content-Type', 'text/html; charset=utf-8');
```

Läs alla headers:

```php
$headers = $response->getHeaders();
```

Läs en header som lista:

```php
$contentType = $response->header('Content-Type');
```

Header-värden ska vara skalära värden.

---

## Skicka response

Response skickas normalt av appens request handler, men manuellt kan den skickas med:

```php
$response->send();
```

Det gör:

- sätter HTTP-statuskod
- skickar headers
- skriver body

I controllers returnerar du normalt response-objektet och låter appen skicka det.

---

## JsonResponse

`JsonResponse` är en specialiserad response för JSON.

Den sätter automatiskt `Content-Type` till:

```text
application/json; charset=utf-8
```

om headern inte redan är satt.

Exempel:

```php
$response = new \Radix\Http\JsonResponse();
$response->setStatusCode(200);
$response->setBody(json_encode([
    'status' => 'ok',
], JSON_THROW_ON_ERROR));

return $response;
```

I API-controllers används ofta en helper:

```php
return $this->json([
    'status' => 'ok',
]);
```

Se mer i:

- [`API.md`](API.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## RedirectResponse

Redirects används ofta efter POST requests.

Exempel:

```php
return redirect(route('home.index'));
```

eller beroende på controller/helper-stil:

```php
return $this->redirect(route('home.index'));
```

En redirect response sätter normalt:

```text
Location
```

och en 3xx-statuskod.

Vanliga redirect-statuskoder:

```text
302 Found
303 See Other
301 Moved Permanently
```

---

## Vanliga statuskoder

### Success

```text
200 OK
201 Created
204 No Content
```

### Redirects

```text
301 Moved Permanently
302 Found
303 See Other
```

### Client errors

```text
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
405 Method Not Allowed
413 Payload Too Large
419/403 CSRF/session-relaterat beroende på app-policy
422 Unprocessable Entity
429 Too Many Requests
```

### Server errors

```text
500 Internal Server Error
503 Service Unavailable
```

---

## Rekommenderade statuskoder för API

Vanliga API-svar:

```text
200 OK                  lyckad GET/uppdatering
201 Created             resurs skapad
204 No Content          lyckad delete utan body
400 Bad Request         ogiltig request
401 Unauthorized        saknar/ogiltig autentisering
403 Forbidden           saknar behörighet
404 Not Found           resurs hittas inte
405 Method Not Allowed  fel HTTP-metod
422 Unprocessable Entity valideringsfel
429 Too Many Requests   rate limit
500 Server Error        oväntat serverfel
```

Exempel på valideringsfel:

```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "Email is required."
    ]
  }
}
```

---

## Headers att ha koll på

Vanliga headers:

```text
Content-Type
Accept
Authorization
Location
Cache-Control
ETag
Last-Modified
X-Request-Id
```

Security headers:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Referrer-Policy
Cross-Origin-Resource-Policy
Permissions-Policy
```

CORS headers:

```text
Access-Control-Allow-Origin
Access-Control-Allow-Methods
Access-Control-Allow-Headers
Access-Control-Allow-Credentials
```

Ofta är det bäst att sätta återkommande headers via middleware.

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)
- [`API.md`](API.md)

---

## Content-Type

För HTML:

```text
text/html; charset=utf-8
```

För JSON:

```text
application/json; charset=utf-8
```

För plain text:

```text
text/plain; charset=utf-8
```

För redirects behövs normalt ingen body-content-type.

---

## Caching headers

För statiska assets hanteras cache ofta av webbservern eller `.htaccess`.

För dynamiska responses kan appen sätta headers som:

```text
Cache-Control: no-store
```

eller:

```text
Cache-Control: public, max-age=31536000, immutable
```

beroende på typ av response.

Se mer i:

- [`CACHE.md`](CACHE.md)
- [`FRONTEND.md`](FRONTEND.md)

---

## CORS

CORS är relevant för API:er som anropas från en annan origin.

Konfiguration kan finnas i:

```text
config/cors.php
```

och `.env`:

```text
CORS_ALLOW_ORIGIN=http://localhost
CORS_ALLOW_CREDENTIALS=1
```

Preflight requests använder HTTP-metoden:

```text
OPTIONS
```

Se mer i:

- [`API.md`](API.md)
- [`SECURITY.md`](SECURITY.md)

---

## Security headers

Security headers sätts normalt via middleware.

Exempel på middleware-alias:

```text
security.headers
```

Relaterad config:

```text
config/security.php
config/csp.php
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)

---

## FormRequest

Radix Framework innehåller en `FormRequest`-bas som kan användas för att strukturera validering av inkommande formulärdata.

Appens egna request-klasser ligger normalt i:

```text
src/Requests/
```

Exempel:

```php
$form = new ContactRequest($this->request);

if (!$form->validate()) {
    return $this->view('contact.index', [
        'errors' => $form->errors(),
    ]);
}
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Testa HTTP endpoints

När du testar endpoints bör du kontrollera:

- statuskod
- headers
- body
- redirects
- JSON-struktur
- auth/unauth-flöden
- rate limit-fall
- CSRF-fall
- valideringsfel

Exempel på saker att asserta:

```text
status är 200
Content-Type är application/json
JSON innehåller nyckeln data
POST utan CSRF nekas
guest redirectas till login
för stor request ger 413
rate limit ger 429
```

Kör tester:

```bash
composer test
```

Se mer i:

- [`TESTING.md`](TESTING.md)

---

## Bra praxis

- returnera alltid ett response-objekt från controllers/handlers
- använd tydliga statuskoder
- använd JSON responses för API
- använd redirects efter lyckade POST-requests
- sätt återkommande headers via middleware
- validera input innan du använder den
- logga inte känsliga headers eller body-data
- kontrollera trusted proxy-inställningar om IP används
- testa både success och failure paths

---

## Felsökning

### Fel statuskod

Kontrollera att controller eller middleware sätter rätt status:

```php
$response->setStatusCode(404);
```

### JSON returneras som text/html

Använd `JsonResponse` eller API-controllerns JSON-helper.

Kontrollera header:

```text
Content-Type: application/json; charset=utf-8
```

### Header saknas

Kontrollera:

- response-koden
- middleware
- om headern skrivs över senare
- om response faktiskt skickas via `send()`

### Session saknas

Om du får fel om att session inte är initialiserad:

- kontrollera session config
- kontrollera middleware/bootstrap
- kontrollera `SESSION_DRIVER`
- kontrollera att session-tabell finns om `SESSION_DRIVER=database`

### IP-adress är fel bakom proxy

Kontrollera trusted proxy-konfiguration.

Se:

- [`CONFIG.md`](CONFIG.md)
- [`SECURITY.md`](SECURITY.md)

### CSRF-token saknas

Kontrollera att formuläret skickar:

```text
csrf_token
```

och att session fungerar.

---

## Relaterat

- [`ROUTING.md`](ROUTING.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`API.md`](API.md)
- [`SECURITY.md`](SECURITY.md)
- [`CACHE.md`](CACHE.md)
- [`TESTING.md`](TESTING.md)
