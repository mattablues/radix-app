# README.md

# Radix App

Radix App är en **starter-applikation** för Radix som skapas via `composer create-project`.

Själva ramverket lever som ett separat Composer-paket:

```text
mattablues/radix-framework
```

> Det här repot är alltså **appen**, inte frameworket.

---

## Radix App vs Radix Framework

```text
radix-app       = starter-projektet / applikationen du bygger vidare på
radix-framework = själva ramverket som appen använder via Composer
```

Radix App innehåller bland annat:

```text
bootstrap/
config/
database/
docs/
public/
resources/
routes/
src/
support/
templates/
tests/
tools/
views/
radix
```

Radix Framework installeras via Composer och ligger normalt under:

```text
vendor/mattablues/radix-framework
```

---

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
<!-- doctoc will insert TOC here -->

- [Radix App vs Radix Framework](#radix-app-vs-radix-framework)
- [Översikt](#%C3%B6versikt)
- [Krav](#krav)
- [Installation](#installation)
- [Webroot och .htaccess](#webroot-och-htaccess)
- [Public assets och uploads](#public-assets-och-uploads)
- [Frontend](#frontend)
- [Dokumentation](#dokumentation)
- [CLI (`radix`)](#cli-radix)
- [Scaffolds](#scaffolds)
- [Utveckling & test](#utveckling--test)
- [Deployment kort](#deployment-kort)
- [Licens](#licens)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

---

## Översikt

Radix App är en färdig projektstruktur med:

- routing
- controllers
- middleware
- views/templates
- frontend assets
- CLI
- migrations
- seeders
- scaffolds
- service/container setup
- test- och analysverktyg

Vanligt flöde:

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
npm install
php radix app:setup
```

Därefter kan du lägga till mer funktionalitet stegvis med scaffolds:

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

---

## Krav

- PHP **8.3**
- Composer
- Node.js + npm
- Databas, till exempel MySQL eller SQLite
- Rekommenderat: webbserver med document root pekad till `public/`

PHP-extensions beror på vilka delar av appen du använder, men appen kan kräva till exempel:

```text
pdo
gd
exif
ctype
openssl
fileinfo
iconv
simplexml
libxml
```

---

## Installation

Skapa ett nytt projekt:

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
```

Installera frontend dependencies:

```bash
npm install
```

Grundsetup:

```bash
php radix app:setup
```

`app:setup` gör normalt:

- rensar cache
- kör migrationer
- kör seeders om det finns några

Se mer:

- [`docs/INSTALLATION.md`](docs/INSTALLATION.md)

---

## Webroot och .htaccess

Rekommenderat är att peka serverns document root till:

```text
public/
```

För enklare webbhotell där document root inte kan ändras finns en `.htaccess` i projektroten som kan skicka requests vidare till `public/`.

Det finns även en:

```text
public/.htaccess
```

som hanterar webbroot-specifika Apache-regler, till exempel:

- routing till `index.php`
- skydd av dolda filer
- cache headers
- regler för assets/uploads

---

## Public assets och uploads

Radix App använder en rekommenderad standardstruktur för publika assets och användaruppladdningar:

```text
public/
  assets/
    css/
    js/
    images/
      graphics/
    favicons/
  uploads/
```

### `public/assets`

`public/assets` innehåller appens betrodda frontend-assets, till exempel:

- CSS
- JavaScript
- favicons
- logotyper
- statisk grafik
- default-avatar

Exempel:

```php
versioned_file('/assets/css/app.css');
versioned_file('/assets/js/app.js');
versioned_file('/assets/images/graphics/avatar.png');
```

### `public/uploads`

`public/uploads` är reserverad för användargenererade filer.

Exempel:

```text
public/uploads/users/1/avatar.jpg
```

Spara normalt publik path i databasen:

```text
/uploads/users/1/avatar.jpg
```

Använd inte `public/uploads` för appens egna SVG-, JS- eller CSS-filer.

Använd inte `public/assets` för användaruppladdningar.

Se mer:

- [`docs/IMAGES.md`](docs/IMAGES.md)
- [`docs/FILES.md`](docs/FILES.md)
- [`docs/SECURITY.md`](docs/SECURITY.md)

---

## Frontend

Frontend-källor ligger i:

```text
resources/
  js/
  tailwind/
```

Byggda filer hamnar i:

```text
public/assets/
  css/app.css
  js/app.js
```

Development:

```bash
npm run start:dev
```

Production build:

```bash
npm run start:build
```

Se mer:

- [`docs/FRONTEND.md`](docs/FRONTEND.md)
- [`docs/TEMPLATES.md`](docs/TEMPLATES.md)

---

## Dokumentation

All dokumentation för appen finns under:

```text
docs/
```

Starta här:

👉 **[Radix App Documentation Index](docs/INDEX.md)**

Viktiga dokument:

- [`docs/INSTALLATION.md`](docs/INSTALLATION.md)
- [`docs/CLI.md`](docs/CLI.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/CONFIG.md`](docs/CONFIG.md)
- [`docs/ROUTING.md`](docs/ROUTING.md)
- [`docs/CONTROLLERS.md`](docs/CONTROLLERS.md)
- [`docs/DATABASE.md`](docs/DATABASE.md)
- [`docs/ORM.md`](docs/ORM.md)
- [`docs/SECURITY.md`](docs/SECURITY.md)
- [`docs/TESTING.md`](docs/TESTING.md)

---

## CLI (`radix`)

Kör CLI:

```bash
php radix
```

Kör ett kommando:

```bash
php radix [command] [arguments]
```

Visa hjälp:

```bash
php radix [command] --help
```

Visa hjälp som Markdown:

```bash
php radix [command] --help --md
```

Vanliga kommandon:

```text
app:setup
scaffold:install
cache:clear

migrations:migrate
migrations:rollback

seeds:run
seeds:rollback

make:migration
make:seeder
make:model
make:controller
make:form-request
make:event
make:listener
make:middleware
make:service
make:provider
make:test
make:view
make:command
```

Se mer:

- [`docs/CLI.md`](docs/CLI.md)

---

## Scaffolds

Scaffolds är paket som kan lägga till app-funktionalitet, till exempel:

- routes
- controllers
- middleware
- views/templates
- config
- migrations
- seeders
- services
- events/listeners
- frontend-filer

Installera scaffold:

```bash
php radix scaffold:install <preset>
```

Exempel:

```bash
php radix scaffold:install auth
```

### Rekommenderat i ny app

I en ny/minimal app kan det finnas placeholder-filer för att statisk analys, till exempel PHPStan, ska vara nöjd.

Använd därför normalt:

```bash
php radix scaffold:install auth --force-placeholders
```

För alla top-level presets:

```bash
php radix scaffold:install --all --force-placeholders
```

Kör sedan:

```bash
php radix migrations:migrate
```

### Kontrollera först med dry-run

```bash
php radix scaffold:install auth --dry-run
```

### Force

Använd `--force` endast när du medvetet vill skriva över riktiga filer:

```bash
php radix scaffold:install auth --force
```

Se mer:

- [`docs/CLI.md`](docs/CLI.md)

---

## Utveckling & test

Vanliga kommandon via Composer scripts:

```bash
composer format:check
composer stan
composer test
```

Autoformat:

```bash
composer format
```

Mutation testing:

```bash
composer infect
composer infect:pcov
composer infect:xdebug
```

Se mer:

- [`docs/TESTING.md`](docs/TESTING.md)

---

## Deployment kort

Exempel på kort deployment-flöde:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run start:build
php radix cache:clear
php radix migrations:migrate
```

Kontrollera production-env:

```dotenv
APP_ENV=production
APP_DEBUG=0
APP_URL=https://example.com
RADIX_DEPLOY=0
MAIL_DEBUG=0
SESSION_COOKIE_SECURE=true
HEALTH_REQUIRE_TOKEN=1
```

Se mer:

- [`docs/SECURITY.md`](docs/SECURITY.md)
- [`docs/CACHE.md`](docs/CACHE.md)
- [`docs/DATABASE.md`](docs/DATABASE.md)

---

## Licens

MIT
