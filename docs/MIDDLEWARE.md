# docs/MIDDLEWARE.md

← [`Tillbaka till index`](INDEX.md)

# Middleware (Radix App)

Middleware körs runt en route handler/controller och kan påverka requesten innan den når din controller eller responsen innan den skickas tillbaka.

Middleware används för till exempel:

- autentisering
- behörighetskontroll
- CSRF-skydd
- request logging
- request-id
- security headers
- CORS/security policy
- rate limiting
- max request size
- IP allowlist
- att dela current user till views

---

## Pipeline-modellen

Förenklat fungerar middleware så här:

```text
Request
  ↓
Middleware A
  ↓
Middleware B
  ↓
Middleware C
  ↓
Controller / route handler
  ↓
Response
  ↓
Middleware C
  ↓
Middleware B
  ↓
Middleware A
  ↓
Client
```

En middleware kan antingen:

1. släppa requesten vidare till nästa steg
2. stoppa requesten och returnera en response direkt

Exempel på när middleware stoppar requesten:

```text
401 Unauthorized
403 Forbidden
404 Not Found
413 Payload Too Large
429 Too Many Requests
redirect till login
```

---

## Var middleware ligger

Appens egna middleware ligger normalt i:

```text
src/Middlewares/
```

Exempel:

```text
src/Middlewares/Auth.php
src/Middlewares/Csrf.php
src/Middlewares/Guest.php
src/Middlewares/IpAllowlist.php
src/Middlewares/LimitRequestSizeWebAware.php
src/Middlewares/Location.php
src/Middlewares/PrivateApp.php
src/Middlewares/RequestLogger.php
src/Middlewares/RequireAdmin.php
src/Middlewares/RequireEditorOrHigher.php
src/Middlewares/RequireModeratorOrHigher.php
src/Middlewares/RequireSupportOrHigher.php
src/Middlewares/RoleRequired.php
src/Middlewares/ShareCurrentUser.php
```

Frameworkets middleware ligger i Composer-paketet under namespace:

```php
Radix\Middleware\Middlewares
```

Exempel:

```php
Radix\Middleware\Middlewares\SecurityHeaders
Radix\Middleware\Middlewares\RequestId
Radix\Middleware\Middlewares\RateLimiter
```

---

## Middleware-konfiguration

Middleware registreras med alias i config.

Vanliga filer:

```text
config/middleware.php
config/middleware.auth.php
config/middleware.admin.php
```

Ett alias gör att du kan använda ett kort namn i routes:

```text
auth
csrf
security.headers
api.throttle
```

i stället för att skriva hela klassnamnet överallt.

---

## Vanliga middleware-alias

Exempel på generella middleware-alias:

```text
canonical.url
security.headers
limit.2mb
limit.web
csrf
private
location
request.id
api.logger
api.throttle
api.throttle.light
api.throttle.hard
```

Exempel på auth/role-relaterade alias:

```text
auth
guest
share.user
role.exact.admin
role.min.moderator
role.min.editor
role.min.support
```

Exempel på admin/security-relaterade alias:

```text
ip.allowlist
```

Exakta alias beror på appens config och installerade scaffolds.

---

## Koppla middleware till route

Middleware kan läggas på en enskild route:

```php
$router->post('/contact', [
    \App\Controllers\ContactController::class,
    'create',
])->name('contact.create')->middleware(['api.throttle.light']);
```

Här används `api.throttle.light` bara på kontaktformulärets POST-route.

---

## Koppla middleware till grupp

Middleware kan också läggas på en hel route-grupp.

Exempel:

```php
$router->group([
    'middleware' => [
        'canonical.url',
        'request.id',
        'api.logger',
        'security.headers',
        'limit.web',
        'csrf',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/', [
        \App\Controllers\HomeController::class,
        'index',
    ])->name('home.index');
});
```

Alla routes i gruppen får då samma middleware.

---

## Middleware med path-prefix

Middleware kombineras ofta med route-prefix.

Exempel:

```php
$router->group([
    'path' => '/admin',
    'middleware' => ['auth', 'role.exact.admin'],
], function (\Radix\Routing\Router $router) {
    $router->get('/dashboard', [
        \App\Controllers\Admin\DashboardController::class,
        'index',
    ])->name('admin.dashboard');
});
```

Det betyder:

```text
/admin/dashboard
  kräver auth
  kräver admin-roll
```

---

## Web middleware

Vanliga web routes kan använda middleware som:

```text
canonical.url
request.id
api.logger
security.headers
limit.web
csrf
```

Exempel:

```php
$router->group([
    'middleware' => [
        'canonical.url',
        'request.id',
        'api.logger',
        'security.headers',
        'limit.web',
        'csrf',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/', [
        \App\Controllers\HomeController::class,
        'index',
    ])->name('home.index');
});
```

### Typiska roller

```text
canonical.url      säkerställer canonical host/url-policy
request.id         sätter request-id
api.logger         loggar request/response
security.headers   lägger på säkerhetsheaders
limit.web          begränsar storlek på web requests
csrf               skyddar state-changing requests
```

---

## API middleware

API routes kan använda middleware som:

```text
request.id
api.logger
security.headers
api.throttle
api.throttle.light
api.throttle.hard
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

För känsligare API-endpoints kan du lägga till throttling:

```php
$router->post('/login', [
    \App\Controllers\Api\AuthController::class,
    'login',
])->middleware(['api.throttle.hard']);
```

---

## Auth middleware

Auth middleware används för routes som kräver inloggning.

Exempel:

```php
$router->group([
    'middleware' => ['auth'],
], function (\Radix\Routing\Router $router) {
    $router->get('/dashboard', [
        \App\Controllers\DashboardController::class,
        'index',
    ])->name('dashboard');
});
```

Typiskt beteende:

```text
inte inloggad -> redirect till login eller 401
inloggad     -> fortsätt till controller
```

---

## Guest middleware

Guest middleware används för routes som bara ska vara tillgängliga för ej inloggade användare.

Exempel:

```php
$router->group([
    'middleware' => ['guest'],
], function (\Radix\Routing\Router $router) {
    $router->get('/login', [
        \App\Controllers\Auth\LoginController::class,
        'index',
    ])->name('auth.login');
});
```

Typiskt beteende:

```text
ej inloggad -> fortsätt
inloggad    -> redirect till dashboard eller hem
```

---

## Share current user

Middleware som `share.user` kan användas för att dela aktuell användare till views/templates.

Exempel:

```php
$router->group([
    'middleware' => ['auth', 'share.user'],
], function (\Radix\Routing\Router $router) {
    $router->get('/dashboard', [
        \App\Controllers\DashboardController::class,
        'index',
    ])->name('dashboard');
});
```

Då kan templates få tillgång till current user beroende på hur view-lagret är uppsatt.

---

## Rollbaserad middleware

Radix App kan ha flera roll-alias:

```text
role.exact.admin
role.min.moderator
role.min.editor
role.min.support
```

Exempel:

```php
$router->group([
    'path' => '/admin',
    'middleware' => ['auth', 'role.exact.admin'],
], function (\Radix\Routing\Router $router) {
    $router->get('/users', [
        \App\Controllers\Admin\UserController::class,
        'index',
    ])->name('admin.users.index');
});
```

Exempel på minsta rollnivå:

```php
$router->group([
    'middleware' => ['auth', 'role.min.editor'],
], function (\Radix\Routing\Router $router) {
    $router->get('/updates', [
        \App\Controllers\ChangelogController::class,
        'index',
    ])->name('updates.index');
});
```

---

## CSRF middleware

CSRF-skydd används för state-changing web requests, till exempel:

```text
POST
PUT
PATCH
DELETE
```

Om en form POST:ar till en route som skyddas av `csrf` behöver formuläret skicka med CSRF-token.

Exempel route:

```php
$router->post('/contact', [
    \App\Controllers\ContactController::class,
    'create',
])->name('contact.create');
```

I controller-actions som ändrar data bör du även följa appens controller-policy, till exempel:

```php
$this->before();
```

Se mer i:

- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`SECURITY.md`](SECURITY.md)

---

## Rate limiting

Rate limiting används för att begränsa hur ofta en klient får anropa en endpoint.

Vanliga alias:

```text
api.throttle
api.throttle.light
api.throttle.hard
```

Exempel:

```php
$router->post('/contact', [
    \App\Controllers\ContactController::class,
    'create',
])->name('contact.create')->middleware(['api.throttle.light']);
```

Exempel på hårdare begränsning:

```php
$router->post('/login', [
    \App\Controllers\Auth\LoginController::class,
    'create',
])->name('auth.login.create')->middleware(['api.throttle.hard']);
```

Cache path för rate limiting kan styras via `.env`, till exempel:

```text
RATELIMIT_CACHE_PATH=cache/ratelimit
```

---

## Security headers

Security headers middleware används för att sätta HTTP-headers som stärker säkerheten.

Exempel på sådant som kan hanteras:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Referrer-Policy
Cross-Origin-Resource-Policy
```

Konfiguration kan finnas i:

```text
config/security.php
config/csp.php
```

och `.env` kan innehålla exempelvis:

```text
SECURITY_CORP=same-origin
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`CONFIG.md`](CONFIG.md)

---

## Request ID

Request ID middleware ger varje request ett id.

Det är användbart för:

- loggning
- felsökning
- koppling mellan app-loggar och webserver-loggar
- supportärenden

Alias:

```text
request.id
```

---

## Request logging

Request logging kan logga inkommande requests och responses.

Alias:

```text
api.logger
```

Trots namnet kan samma logger användas i både web och API beroende på hur routes är uppsatta.

Tänk på att inte logga känslig data, till exempel:

- lösenord
- tokens
- cookies
- personuppgifter i onödan

Se mer i:

- [`LOGGING.md`](LOGGING.md)

---

## Request size limit

Middleware kan begränsa maxstorlek på requests.

Vanliga alias:

```text
limit.2mb
limit.web
```

I `.env` kan web request-gränsen styras med exempelvis:

```text
WEB_MAX_REQUEST_MB=6
```

Det skyddar mot för stora POST bodies/uploads på fel endpoints.

---

## Private app

Middleware som `private` kan användas för att stänga publik åtkomst eller registrering beroende på appens policy.

Relaterad `.env`-nyckel:

```text
APP_PRIVATE=0
```

Exakt beteende beror på middleware-implementationen och auth-scaffoldet.

---

## Location middleware

Middleware som `location` kan använda locator/geolocation-relaterad konfiguration.

Relaterade `.env`-nycklar kan vara:

```text
LOCATOR_COUNTRY=Sweden
LOCATOR_CITY=Stockholm
LOCATOR_CITY_URL=https://www.klart.se/se/stockholms-l%C3%A4n/stockholm/

GEOLOCATOR_ENABLED=0
GEOLOCATOR_BASE_URL=http://ip-api.com/json
GEOLOCATOR_TIMEOUT=2
```

Se mer i:

- [`GEOLOCATION.md`](GEOLOCATION.md)

---

## IP allowlist

IP allowlist används för att begränsa vissa routes till godkända IP-adresser.

Alias:

```text
ip.allowlist
```

Relaterad `.env`-nyckel:

```text
HEALTH_IP_ALLOWLIST=127.0.0.1,::1
```

Det är särskilt användbart för health endpoints, admin endpoints eller interna API:er.

---

## Skapa middleware

Skapa middleware via CLI:

```bash
php radix make:middleware AuditLog
```

Det skapar normalt en middleware under:

```text
src/Middlewares/
```

Kör hjälp för exakta argument/options:

```bash
php radix make:middleware --help
```

Efter att du skapat middleware behöver du normalt registrera den i middleware-konfigurationen.

Exempel:

```php
return [
    'audit.log' => \App\Middlewares\AuditLog::class,
];
```

Sedan kan den användas i routes:

```php
$router->get('/admin/audit', [
    \App\Controllers\Admin\AuditController::class,
    'index',
])->middleware(['auth', 'role.exact.admin', 'audit.log']);
```

---

## Middleware från scaffolds

Scaffolds kan lägga till eller uppdatera middleware och middleware-config.

Exempel:

```bash
php radix scaffold:install auth --force-placeholders
```

Efter scaffold-installation kan det vara bra att rensa cache:

```bash
php radix cache:clear
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Rekommenderad ordning

Ordningen kan spela roll.

Exempel för web routes:

```text
canonical.url
request.id
api.logger
security.headers
limit.web
csrf
auth
share.user
```

Exempel för API routes:

```text
request.id
api.logger
security.headers
api.throttle
auth/token middleware
```

Generella principer:

1. normalisera request tidigt
2. sätt request-id tidigt
3. logga runt hela kedjan
4. sätt security headers tidigt
5. stoppa för stora requests innan dyr logik körs
6. kör auth/roles innan skyddade controllers
7. kör controller sist

---

## Testa middleware

Middleware bör testas för minst två fall:

1. request stoppas korrekt
2. request släpps vidare korrekt

Exempel på testfall:

```text
guest till /dashboard -> redirect till login
user till /dashboard -> 200 OK
user utan admin-roll till /admin -> 403 Forbidden
för stor request -> 413 Payload Too Large
för många requests -> 429 Too Many Requests
```

Kör tester:

```bash
composer test
```

Kör statisk analys:

```bash
composer stan
```

---

## Bra praxis

- håll middleware små
- en middleware bör ha ett tydligt ansvar
- använd alias i routes, inte hårdkodade klassnamn
- lägg känslig policy i middleware/config
- logga inte secrets eller lösenord
- testa både stop/pass-fall
- använd `--dry-run` innan scaffolds i befintliga appar
- rensa cache efter configändringar

---

## Felsökning

### Middleware körs inte

Kontrollera:

- att aliaset finns i config
- att route eller grupp använder aliaset
- att route-filen laddas
- att cache är rensad

Kör:

```bash
php radix cache:clear
```

### Fel alias

Om du använder:

```php
->middleware(['auth'])
```

måste `auth` finnas registrerat i middleware-konfigurationen.

### Fel namespace

Appens middleware ligger normalt i:

```php
App\Middlewares
```

inte:

```php
App\Middleware
```

### Request stoppas oväntat

Kontrollera:

- auth state
- session
- CSRF-token
- roll/behörighet
- IP allowlist
- rate limit-cache
- request size
- security policy

### CSRF-fel

Kontrollera:

- att session fungerar
- att formuläret skickar token
- att route skyddas av `csrf`
- att controller följer appens `before()`-policy

### Rate limit slår för hårt

Kontrollera:

```text
RATELIMIT_CACHE_PATH
```

Rensa cache vid behov:

```bash
php radix cache:clear
```

---

## Relaterat

- [`ROUTING.md`](ROUTING.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`HTTP.md`](HTTP.md)
- [`SECURITY.md`](SECURITY.md)
- [`CONFIG.md`](CONFIG.md)
- [`LOGGING.md`](LOGGING.md)
- [`GEOLOCATION.md`](GEOLOCATION.md)
- [`TESTING.md`](TESTING.md)
