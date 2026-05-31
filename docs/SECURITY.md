# docs/SECURITY.md

← [`Tillbaka till index`](INDEX.md)

# Säkerhet (Radix App)

Den här guiden beskriver viktiga säkerhetsdelar i **Radix App**.

Säkerhet i Radix App handlar framför allt om:

- webroot och filstruktur
- `.env` och secrets
- security headers
- CSP
- CORS
- CSRF
- sessions och cookies
- rate limiting
- input-validering
- uploads
- auth och behörigheter
- logging utan känslig data
- production-säkra defaults

---

## Grundprinciper

Rekommenderade principer:

```text
public/ är webroot
.env innehåller secrets
APP_DEBUG=0 i production
uploads separeras från assets
input valideras alltid
write-actions skyddas med CSRF
API skyddas med token/auth/rate limit
security headers sätts via middleware
loggar innehåller inte secrets
```

---

## Webroot

Rekommenderat är att webbserverns document root pekar på:

```text
public/
```

Då exponeras inte appens källkod, config, templates eller vendor-katalog direkt.

Bra:

```text
https://example.com -> /path/to/app/public
```

Undvik att exponera hela projektroten som webroot.

Se mer i:

- [`INSTALLATION.md`](INSTALLATION.md)

---

## `.env` och secrets

`.env` ska normalt inte commit:as.

Den kan innehålla secrets som:

```text
API_TOKEN
SECURE_TOKEN_HMAC
SECURE_ENCRYPTION_KEY
MAIL_PASSWORD
DB_PASSWORD
```

Rekommendationer:

- använd starka slumpmässiga värden
- ha olika secrets per miljö
- rotera nycklar vid misstänkt läckage
- håll `.env.example` utan riktiga secrets
- lägg aldrig production-secrets i repo

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## Production checklista

Minst följande bör kontrolleras i production:

```dotenv
APP_ENV=production
APP_DEBUG=0
APP_URL=https://example.com
RADIX_DEPLOY=0
```

Sessions/cookies:

```dotenv
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

Om du använder `__Host-` cookie-namn:

```dotenv
SESSION_COOKIE_NAME=__Host-radix_session
```

API/security:

```dotenv
API_TOKEN=<strong-secret>
HEALTH_REQUIRE_TOKEN=1
SECURITY_CORP=same-origin
```

Mail:

```dotenv
MAIL_DEBUG=0
```

---

## Debug och fel

I production:

```dotenv
APP_DEBUG=0
```

Debug ska inte vara aktivt i production eftersom stack traces, paths och configdetaljer kan läcka.

I development kan du använda:

```dotenv
APP_DEBUG=1
```

men var ändå försiktig med känslig data i loggar och dumps.

---

## Security headers

Security headers sätts normalt via middleware.

Vanligt middleware-alias:

```text
security.headers
```

Relaterad config:

```text
config/security.php
config/csp.php
```

Exempel på headers som kan sättas:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Referrer-Policy
Cross-Origin-Resource-Policy
Permissions-Policy
Strict-Transport-Security
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Cross-Origin-Resource-Policy

Config:

```text
config/security.php
```

Env:

```dotenv
SECURITY_CORP=same-origin
```

Tillåtna värden:

```text
same-origin
same-site
cross-origin
off
```

Rekommenderat default:

```dotenv
SECURITY_CORP=same-origin
```

Använd `cross-origin` endast om resurser avsiktligt ska kunna bäddas in från andra origins.

---

## CSP (Content Security Policy)

CSP hjälper till att minska risken för XSS och otillåtna externa resurser.

Config:

```text
config/csp.php
```

Exempel på policyområden:

```text
default-src
script-src
style-src
img-src
font-src
connect-src
frame-ancestors
form-action
```

En strikt web policy kan till exempel ha:

```text
default-src 'none'
script-src 'self' 'nonce-...'
style-src 'self'
img-src 'self' data:
frame-ancestors 'none'
form-action 'self'
```

---

## CSP nonce

Om script kräver nonce ska script-taggen ha rätt nonce.

Exempel:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Det gör att CSP kan tillåta just detta script för aktuell request.

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## HSTS

HSTS bör bara aktiveras i production över HTTPS.

I config kan HSTS aktiveras baserat på environment.

Rekommendation:

```text
APP_ENV=production
APP_URL=https://example.com
```

Aktivera inte HSTS på lokala HTTP-miljöer.

---

## CORS

CORS styr vilka origins som får anropa API:er från browser.

Config:

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
- använd inte `*` tillsammans med credentials
- begränsa CORS till API-paths
- låt preflight hanteras konsekvent

---

## CORS paths

CORS kan begränsas till API-prefix, till exempel:

```text
/api/v1/
```

Det gör att CORS inte behöver gälla hela webappen.

---

## CORS headers

Vanliga CORS-inställningar:

```text
allow_origins
allow_methods
allow_headers
expose_headers
max_age
allow_credentials
```

Exempel på tillåtna headers:

```text
Authorization
Content-Type
X-Requested-With
X-CSRF-Token
X-Request-Id
```

---

## CSRF

CSRF skyddar web forms från cross-site request forgery.

För POST/PUT/PATCH/DELETE i web routes bör CSRF-skydd vara aktivt.

Vanligt middleware-alias:

```text
csrf
```

I formulär:

```html
<form method="post" action="{{ route('contact.create') }}">
    {{ csrf_field()|raw }}

    <button type="submit">Skicka</button>
</form>
```

I controllers som ändrar data bör du normalt köra:

```php
$this->before();
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Sessions

Radix App kan använda:

```text
SESSION_DRIVER=file
SESSION_DRIVER=database
```

Vid första installation rekommenderas ofta:

```dotenv
SESSION_DRIVER=file
```

Byt till database sessions efter att session-tabellen finns.

Se mer i:

- [`CONFIG.md`](CONFIG.md)
- [`DATABASE.md`](DATABASE.md)

---

## Session cookie security

Vanliga inställningar:

```dotenv
SESSION_COOKIE_NAME=radix_session
SESSION_COOKIE_SECURE=auto
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

För production över HTTPS:

```dotenv
SESSION_COOKIE_NAME=__Host-radix_session
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

`__Host-` kräver normalt:

```text
Secure
Path=/
ingen Domain
HTTPS
```

---

## Rate limiting

Rate limiting skyddar mot abuse.

Vanliga middleware-alias:

```text
api.throttle
api.throttle.light
api.throttle.hard
```

Använd extra strikt rate limiting för:

```text
login
password reset
registration
contact forms
publika API endpoints
search endpoints
health endpoints om publika
```

Rate limit-cache kan styras via:

```dotenv
RATELIMIT_CACHE_PATH=cache/ratelimit
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`CACHE.md`](CACHE.md)

---

## Request size limits

Begränsa maxstorlek på requests.

Vanlig env:

```dotenv
WEB_MAX_REQUEST_MB=6
```

Vanliga middleware-alias:

```text
limit.web
limit.2mb
```

Det skyddar mot för stora payloads och felaktiga uploads.

---

## Trusted proxies

Om appen kör bakom reverse proxy/load balancer behöver IP-hantering vara korrekt.

Env:

```dotenv
TRUSTED_PROXY=
```

Sätt bara betrodda proxy-IP:n.

Exempel:

```dotenv
TRUSTED_PROXY=127.0.0.1
```

eller:

```dotenv
TRUSTED_PROXY=10.0.0.1
```

Om detta är fel kan följande påverkas:

- rate limiting
- IP allowlist
- logging
- geolocation
- audit trails

---

## IP allowlist

Vissa endpoints kan begränsas med IP allowlist.

Exempel:

```dotenv
HEALTH_IP_ALLOWLIST=127.0.0.1,::1
```

Vanligt middleware-alias:

```text
ip.allowlist
```

Det kan användas för till exempel health endpoints eller interna admin/API-flöden.

---

## API tokens

API-relaterade env-värden:

```dotenv
API_TOKEN=
HEALTH_REQUIRE_TOKEN=1
```

Rekommendationer:

- använd stark token
- rotera vid behov
- skicka över HTTPS
- logga inte token
- använd `HEALTH_REQUIRE_TOKEN=1` i production om health-data är känslig

Se mer i:

- [`API.md`](API.md)

---

## Input-validering

Validera alltid input.

Använd:

```php
Radix\Support\Validator
Radix\Http\FormRequest
```

Exempel:

```php
$validator = new \Radix\Support\Validator($request->post, [
    'email' => 'required|email',
    'message' => 'required|max:5000',
]);
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Output escaping

Templates auto-escapar normal output:

```html
{{ $userInput }}
```

Använd inte `|raw` på osäker input:

```html
{{ $userInput|raw }}
```

`|raw` ska bara användas för säkert och kontrollerat innehåll.

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Upload security

Användaruppladdningar ska hanteras strikt.

Rekommendationer:

- validera MIME och storlek
- använd genererade filnamn
- spara i `public/uploads/`, inte `public/assets/`
- tillåt bara filtyper du behöver
- tillåt inte PHP/HTML/JS
- var försiktig med SVG
- privata filer ska ligga utanför `public/`
- radera gamla filer säkert

Exempelregler:

```php
$rules = [
    'avatar' => 'required|file_type:image/jpeg,image/png,image/webp|file_size:2',
];
```

Se mer i:

- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)

---

## `.htaccess` och uploads

Uploads kan skyddas med striktare `.htaccess`.

Syfte:

- stoppa script-exekvering
- begränsa filtyper
- minska risk från användargenererade filer

Även med `.htaccess` ska appen validera uploads noggrant.

---

## Auth och behörighet

Skydda routes med middleware.

Exempel:

```php
$router->group([
    'middleware' => ['auth'],
], function (\Radix\Routing\Router $router) {
    // inloggade routes
});
```

Admin:

```php
$router->group([
    'path' => '/admin',
    'middleware' => ['auth', 'role.exact.admin'],
], function (\Radix\Routing\Router $router) {
    // admin routes
});
```

Vanliga role-alias:

```text
role.exact.admin
role.min.moderator
role.min.editor
role.min.support
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Private app

Radix App kan ha privat-läge:

```dotenv
APP_PRIVATE=0
```

När aktivt kan appen begränsa publik registrering eller publik åtkomst beroende på middleware/scaffold.

---

## Logging security

Logga aldrig secrets.

Undvik att logga:

```text
password
password_confirmation
API_TOKEN
Authorization
Cookie
Set-Cookie
csrf_token
SECURE_TOKEN_HMAC
SECURE_ENCRYPTION_KEY
MAIL_PASSWORD
DB_PASSWORD
```

Se mer i:

- [`LOGGING.md`](LOGGING.md)

---

## Mail security

För mailflöden:

- logga inte reset/activation tokens i onödan
- använd HTTPS-länkar i production
- håll SMTP-credentials i `.env`
- använd `MAIL_DEBUG=0` i production
- escapa användarinput i mailtemplates

Se mer i:

- [`MAIL.md`](MAIL.md)

---

## Database security

Rekommendationer:

- använd DB-användare med minsta nödvändiga rättigheter
- använd prepared statements/query builder
- validera input innan writes
- kör backup innan production-migrations
- använd inte `app:setup --fresh` i production

Se mer i:

- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)

---

## CLI och production safety

Vissa CLI-kommandon är känsliga:

```bash
php radix migrations:migrate
php radix migrations:rollback
php radix app:setup --fresh
```

Om appen använder deploy-skydd:

```dotenv
RADIX_DEPLOY=0
```

Sätt deploy-flagga bara för en kontrollerad körning.

Se mer i:

- [`CLI.md`](CLI.md)
- [`DATABASE.md`](DATABASE.md)

---

## Geolocation

IP-geolocation ska inte användas som enda säkerhetskritiska kontroll.

Env:

```dotenv
GEOLOCATOR_ENABLED=0
GEOLOCATOR_BASE_URL=http://ip-api.com/json
GEOLOCATOR_TIMEOUT=2
```

Tänk på:

- privacy/GDPR
- felaktig IP bakom proxy
- externa tjänsters tillgänglighet
- rate limits

Se mer i:

- [`GEOLOCATION.md`](GEOLOCATION.md)

---

## Frontend security

Rekommendationer:

- använd CSP
- använd nonce för scripts om CSP kräver det
- undvik inline-script där möjligt
- använd `{{ ... }}` för user content
- använd `|raw` sparsamt
- använd `versioned_file()` för assets
- håll Alpine/JS bundlat via `resources/js`

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Headers och API

För API bör du tänka på:

- korrekt `Content-Type`
- CORS policy
- token/auth
- rate limiting
- inga stack traces i JSON-fel
- konsekventa statuskoder

Se mer i:

- [`API.md`](API.md)
- [`HTTP.md`](HTTP.md)

---

## Security checklist för ny app

Efter installation:

```bash
php radix app:setup
php radix cache:clear
```

Kontrollera:

```text
APP_DEBUG=0 i production
APP_URL korrekt
SESSION_COOKIE_* korrekt
API_TOKEN satt om API/health kräver token
MAIL_DEBUG=0 i production
CORS_ALLOW_ORIGIN inte wildcard i production
SECURITY_CORP=same-origin
TRUSTED_PROXY satt endast om proxy används
```

---

## Security checklist före deploy

```text
APP_ENV=production
APP_DEBUG=0
APP_URL=https://...
RADIX_DEPLOY=0
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
MAIL_DEBUG=0
HEALTH_REQUIRE_TOKEN=1
API_TOKEN satt
CORS strikt
CSP verifierad
uploads skyddade
storage/cache/logs inte publika
```

Kör:

```bash
composer install --no-dev --optimize-autoloader
npm run start:build
php radix cache:clear
```

Migrationer, om aktuellt:

```bash
php radix migrations:migrate
```

---

## Felsökning

### CSP blockerar script

Kontrollera att script-taggen har nonce:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Kontrollera `config/csp.php`.

### CORS fungerar inte

Kontrollera:

```text
config/cors.php
CORS_ALLOW_ORIGIN
CORS_ALLOW_CREDENTIALS
```

Kontrollera att request path matchar CORS paths.

### CSRF-fel

Kontrollera:

- session fungerar
- formuläret har `csrf_field()`
- route använder `csrf` middleware
- controller kör `$this->before()` där det behövs

### Rate limit fastnar

Rensa cache:

```bash
php radix cache:clear
```

Kontrollera:

```text
RATELIMIT_CACHE_PATH
```

### IP är fel

Kontrollera proxy/load balancer och `TRUSTED_PROXY`.

### Upload nekas

Kontrollera:

- MIME-typ
- filstorlek
- PHP `upload_max_filesize`
- `post_max_size`
- upload-katalogens rättigheter
- `.htaccess`

---

## Bra praxis

- webroot ska vara `public/`
- secrets ska ligga i `.env`
- debug av i production
- använd CSRF på web forms
- använd rate limiting på känsliga endpoints
- använd CORS restriktivt
- använd CSP och nonce om möjligt
- validera input
- escapa output
- separera assets och uploads
- logga inte känslig data
- använd HTTPS i production
- håll dependencies uppdaterade

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`HTTP.md`](HTTP.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`FRONTEND.md`](FRONTEND.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`FILES.md`](FILES.md)
- [`IMAGES.md`](IMAGES.md)
- [`API.md`](API.md)
- [`LOGGING.md`](LOGGING.md)
