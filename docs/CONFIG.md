# docs/CONFIG.md

← [`Tillbaka till index`](INDEX.md)

# Konfiguration (Radix App)

Den här guiden beskriver hur konfigurationen i **Radix App** är upplagd.

Radix App använder konfiguration från:

```text
.env
config/*.php
```

`.env` innehåller miljöspecifika värden, secrets och lokala inställningar.

`config/*.php` innehåller appens konfigurationsstruktur och standardbeteenden.

---

## Grundprincip

Radix App skiljer på:

```text
.env          = miljöspecifika värden
config/*.php = appens konfigurationsfiler
```

Exempel:

```text
APP_ENV=development
APP_DEBUG=0
DB_DRIVER=mysql
DB_HOST=127.0.0.1
SESSION_DRIVER=file
```

Appen läser `.env` tidigt under boot och använder sedan värdena i konfigurationsfilerna.

---

## Var konfigurationen finns

Vanliga platser:

```text
.env
.env.example
config/
cache/
storage/
```

### `.env`

Din lokala miljöfil.

Den ska normalt inte commit:as.

### `.env.example`

Mall för vilka miljövariabler som kan behövas.

Den ska hållas uppdaterad när nya env-nycklar läggs till.

### `config/`

Appens PHP-baserade konfiguration.

### `cache/`

Cacheade filer, till exempel app-cache, view-cache eller andra runtime-filer.

### `storage/`

Persistenta runtime-filer, till exempel sessions eller andra filer beroende på setup.

---

## Config-filer

Radix App kan innehålla config-filer som:

```text
config/app.php
config/commands.php
config/cors.php
config/csp.php
config/database.php
config/datetime.php
config/email.php
config/listeners.php
config/listeners.auth.php
config/mail.php
config/middleware.php
config/middleware.auth.php
config/middleware.admin.php
config/orm.php
config/pluralization.php
config/providers.php
config/routes.php
config/security.php
config/services.php
config/session.php
config/translations.php
```

Vilka filer som finns kan variera beroende på version och installerade scaffolds.

---

## Viktigt om `.env`

Appen förväntar sig att `.env` finns.

Vid `composer create-project`, `composer install` och `composer update` kan appens bootstrap-script skapa eller uppdatera `.env` och generera secrets vid behov.

Om du behöver skapa `.env` manuellt:

```bash
cp .env.example .env
```

På Windows kan du till exempel kopiera filen i Explorer eller köra:

```powershell
Copy-Item .env.example .env
```

Kontrollera därefter värdena i `.env`.

---

## Viktiga app-inställningar

Vanliga appvärden:

```text
APP_ENV=development
APP_DEBUG=0
APP_LANG=sv
APP_NAME="Radix System"
APP_TIMEZONE=Europe/Stockholm
APP_URL=http://localhost
APP_COPY="Ditt Företag"
APP_COPY_YEAR=2026
APP_MAINTENANCE=0
```

### `APP_ENV`

Anger miljö.

Vanliga värden:

```text
production
development
local
test
```

### `APP_DEBUG`

Aktiverar debug-läge.

```text
APP_DEBUG=1
```

ska bara användas lokalt eller i utvecklingsmiljö.

I produktion:

```text
APP_DEBUG=0
```

### `APP_URL`

Bas-URL för appen.

Exempel:

```text
APP_URL=http://localhost
```

eller:

```text
APP_URL=https://example.com
```

### `APP_TIMEZONE`

Tidszon för appen.

Exempel:

```text
APP_TIMEZONE=Europe/Stockholm
```

---

## Underhållsläge

Radix App kan ha stöd för underhållsläge via:

```text
APP_MAINTENANCE=0
```

Aktivera:

```text
APP_MAINTENANCE=1
```

Hur underhållsläget används beror på appens middleware/config.

Se även:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)

---

## CLI och production safety

Vissa CLI-kommandon kan vara känsliga i produktion, särskilt sådana som ändrar databas eller deployment-state.

Radix App kan använda:

```text
RADIX_DEPLOY=0
```

För en enskild deploy-körning kan du sätta:

```text
RADIX_DEPLOY=1
```

Rekommendation:

- låt inte `RADIX_DEPLOY=1` ligga permanent i production
- använd det bara vid medvetna deploy-kommandon
- återställ efter körning

Exempel på känsliga kommandon:

```bash
php radix migrations:migrate
php radix migrations:rollback
```

---

## Databas

Vanliga databasvärden:

```text
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=radix
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### MySQL

Exempel:

```text
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=radix
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### SQLite

Om appen är konfigurerad för SQLite kan driver sättas till:

```text
DB_DRIVER=sqlite
```

Hur databasfilen anges beror på `config/database.php`.

Efter databasändringar eller nya migrations:

```bash
php radix migrations:migrate
```

Se mer i:

- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)

---

## Sessions

Radix App stödjer normalt sessions via:

```text
SESSION_DRIVER=file
```

eller:

```text
SESSION_DRIVER=database
```

### File sessions

Rekommenderat vid första installation:

```text
SESSION_DRIVER=file
SESSION_FILE_PATH=storage/sessions
```

Det är säkrast eftersom det inte kräver att session-tabellen redan finns.

### Database sessions

Database sessions kräver att session-tabellen finns.

Typiskt flöde:

1. Börja med `SESSION_DRIVER=file`
2. Kör setup/migrationer
3. Byt till `SESSION_DRIVER=database`
4. Kör appen igen

Exempel:

```text
SESSION_DRIVER=database
SESSION_TABLE=sessions
SESSION_LIFETIME=1440
```

Kör migrationer:

```bash
php radix migrations:migrate
```

---

## Session cookies

Vanliga session cookie-inställningar:

```text
SESSION_COOKIE_NAME=radix_session
SESSION_COOKIE_SECURE=auto
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

### Production

I production över HTTPS rekommenderas en säker cookie-konfiguration.

Exempel:

```text
SESSION_COOKIE_NAME=__Host-radix_session
SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax
```

`__Host-` kräver normalt:

- HTTPS
- Secure cookie
- Path `/`
- ingen Domain-attribut

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Cache paths

Vanliga cachevärden:

```text
CACHE_ROOT=cache
VIEWS_CACHE_PATH=cache/views
APP_CACHE_PATH=cache/app
HEALTH_CACHE_PATH=cache/health
RATELIMIT_CACHE_PATH=cache/ratelimit
```

Relativa sökvägar utgår normalt från projektroten.

Efter ändringar i config eller templates kan du rensa cache:

```bash
php radix cache:clear
```

---

## Mail

Vanliga mailvärden:

```text
MAIL_DEBUG=0
MAIL_CHARSET=UTF-8
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_SECURE=tls
MAIL_AUTH=0
MAIL_ACCOUNT=
MAIL_PASSWORD=
MAIL_EMAIL=noreply@example.com
MAIL_FROM="Radix System"
```

För lokal utveckling används ofta Mailtrap eller liknande SMTP-tjänst.

I production ska du använda riktiga SMTP-uppgifter och hålla credentials i `.env`.

Se mer i:

- [`MAIL.md`](MAIL.md)

---

## API och säkerhet

Vanliga API/security-värden:

```text
API_TOKEN=
HEALTH_REQUIRE_TOKEN=1
SECURITY_CORP=same-origin
HEALTH_IP_ALLOWLIST=127.0.0.1,::1
TRUSTED_PROXY=
```

### `API_TOKEN`

Används för skyddade API- eller health-endpoints om appen är konfigurerad så.

### `HEALTH_REQUIRE_TOKEN`

Om satt till `1` krävs token för health-data.

```text
HEALTH_REQUIRE_TOKEN=1
```

### `SECURITY_CORP`

Cross-Origin-Resource-Policy.

Vanliga värden:

```text
same-origin
same-site
cross-origin
off
```

### `HEALTH_IP_ALLOWLIST`

Kommaseparerad lista av IP/CIDR som får anropa health endpoints i production.

Exempel:

```text
HEALTH_IP_ALLOWLIST=127.0.0.1,::1
```

### `TRUSTED_PROXY`

Sätt bara om appen kör bakom en reverse proxy eller load balancer och du vill lita på forwarded headers.

Exempel:

```text
TRUSTED_PROXY=127.0.0.1
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`API.md`](API.md)

---

## CORS

Vanliga CORS-värden:

```text
CORS_ALLOW_ORIGIN=http://localhost
CORS_ALLOW_CREDENTIALS=1
```

Om appen har API:er som anropas från en annan origin behöver CORS-konfigurationen vara korrekt.

Se mer i:

- [`API.md`](API.md)
- [`SECURITY.md`](SECURITY.md)

---

## CSP

Content Security Policy konfigureras via appens CSP-config, normalt:

```text
config/csp.php
```

CSP används för att minska risken för XSS och oönskade externa resurser.

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Geolocation

Radix App kan ha geolocation-inställningar:

```text
GEOLOCATOR_ENABLED=0
GEOLOCATOR_BASE_URL=http://ip-api.com/json
GEOLOCATOR_TIMEOUT=2
```

Rekommendation:

- använd inte IP-geolocation som enda säkerhetskritiska kontroll
- tänk på privacy/GDPR
- free tier-tjänster kan ha begränsningar

Se mer i:

- [`GEOLOCATION.md`](GEOLOCATION.md)

---

## Locator settings

Appen kan också ha locator-värden för till exempel systemhälsa, väder eller platsinformation:

```text
LOCATOR_COUNTRY=Sweden
LOCATOR_CITY=Stockholm
LOCATOR_CITY_URL=https://www.klart.se/se/stockholms-l%C3%A4n/stockholm/
```

Hur de används beror på appens installerade funktionalitet/scaffolds.

---

## ORM

Vanlig ORM-inställning:

```text
ORM_MODEL_NAMESPACE=
```

Exempel:

```text
ORM_MODEL_NAMESPACE="App\\Models\\"
```

Om namespace lämnas tomt kan app/framework använda default enligt config.

Se mer i:

- [`ORM.md`](ORM.md)

---

## Encryption keys och secrets

Vanliga secrets:

```text
API_TOKEN=
SECURE_TOKEN_HMAC=
SECURE_ENCRYPTION_KEY=
```

Dessa kan genereras automatiskt om de saknas, beroende på appens env-bootstrap.

Rekommendationer:

- commit:a aldrig riktiga secrets
- använd starka slumpmässiga värden i production
- rotera nycklar vid misstänkt läckage
- håll `.env.example` utan riktiga secrets

---

## Routes config

Route-konfiguration ligger normalt i:

```text
config/routes.php
```

Route-filer ligger normalt under:

```text
routes/
```

Scaffolds kan lägga till fler route-filer.

Se mer i:

- [`ROUTING.md`](ROUTING.md)
- [`CLI.md`](CLI.md)

---

## Middleware config

Middleware-konfiguration kan ligga i filer som:

```text
config/middleware.php
config/middleware.auth.php
config/middleware.admin.php
```

Scaffolds kan lägga till eller uppdatera middleware-konfiguration.

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Commands config

CLI-kommandon kan registreras i:

```text
config/commands.php
```

App-specifika kommandon kan ligga i:

```text
src/Console/Commands/
```

Exempel:

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Providers config

Service providers registreras normalt i:

```text
config/providers.php
```

Providers används för att koppla in services, listeners och annan bootstrapping.

Se mer i:

- [`SERVICES.md`](SERVICES.md)

---

## Listeners config

Event listeners kan konfigureras i till exempel:

```text
config/listeners.php
config/listeners.auth.php
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Translations och språk

Vanliga språkrelaterade värden:

```text
APP_LANG=sv
```

Det kan även finnas config för översättningar:

```text
config/translations.php
```

Hur översättningar används beror på appens implementation och installerade scaffolds.

---

## När du ska rensa cache

Rensa cache efter ändringar i till exempel:

- config
- providers
- services
- commands
- routes
- middleware
- templates
- scaffolds

Kör:

```bash
php radix cache:clear
```

---

## `.env.example` ska hållas uppdaterad

När du lägger till en ny env-nyckel i config eller kod, uppdatera även:

```text
.env.example
```

Men lägg aldrig in riktiga production-värden eller secrets där.

Bra exempel:

```text
API_TOKEN=
MAIL_PASSWORD=
SECURE_ENCRYPTION_KEY=
```

Dåliga exempel:

```text
API_TOKEN=real-production-token
MAIL_PASSWORD=real-password
SECURE_ENCRYPTION_KEY=real-secret-key
```

---

## Rekommenderade production-värden

Minst följande bör kontrolleras i production:

```text
APP_ENV=production
APP_DEBUG=0
APP_URL=https://example.com

SESSION_COOKIE_SECURE=true
SESSION_COOKIE_HTTPONLY=true
SESSION_COOKIE_SAMESITE=Lax

RADIX_DEPLOY=0
```

Och se till att riktiga secrets är satta:

```text
API_TOKEN=
SECURE_TOKEN_HMAC=
SECURE_ENCRYPTION_KEY=
MAIL_PASSWORD=
```

---

## Felsökning

### Appen hittar inte `.env`

Kontrollera att filen finns i projektroten:

```text
.env
```

Om den saknas, skapa från exempel:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### Databasanslutning misslyckas

Kontrollera:

```text
DB_DRIVER
DB_HOST
DB_PORT
DB_NAME
DB_USERNAME
DB_PASSWORD
```

Kör sedan:

```bash
php radix migrations:migrate
```

### Database sessions fungerar inte

Börja med:

```text
SESSION_DRIVER=file
```

Kör:

```bash
php radix migrations:migrate
```

Byt därefter till:

```text
SESSION_DRIVER=database
```

### Ändringar i config märks inte

Rensa cache:

```bash
php radix cache:clear
```

### CLI-kommando dyker inte upp

Kontrollera:

```text
config/commands.php
```

Rensa cache:

```bash
php radix cache:clear
```

Visa listan:

```bash
php radix
```

---

## Relaterat

- [`INSTALLATION.md`](INSTALLATION.md)
- [`CLI.md`](CLI.md)
- [`ARCHITECTURE.md`](ARCHITECTURE.md)
- [`DATABASE.md`](DATABASE.md)
- [`ROUTING.md`](ROUTING.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)
