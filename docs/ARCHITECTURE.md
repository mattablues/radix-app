# docs/ARCHITECTURE.md

← [`Tillbaka till index`](INDEX.md)

# Arkitekturöversikt (Radix App)

Det här dokumentet beskriver hur **Radix App** är uppbyggd och hur den använder **Radix Framework**.

Radix App är starter-applikationen: projektstruktur, konfiguration, routes, controllers, views, assets, app-specifik kod och CLI-entrypoint.

Radix Framework är Composer-paketet som innehåller ramverkets grunddelar, till exempel routing, HTTP, container, console, databas, ORM, templates och helpers.

```text
radix-app       = applikationen du bygger i
radix-framework = ramverket appen använder via Composer
```

---

## Översikt

En typisk Radix App består av:

```text
bootstrap/      Bootstrapping av appen
config/         App-konfiguration
database/       Migrations, seeders och databasrelaterad appkod
docs/           Dokumentation
public/         Webroot, index.php, publika assets och uploads
resources/      Frontend-källor, t.ex. JS och Tailwind/CSS
routes/         Route-filer
src/            Appens PHP-kod
support/        App-specifika helpers/support-filer
templates/      Stubs och templates för generatorer/scaffolds
tests/          Tester
tools/          Projektverktyg
views/          Ratio templates/views
radix           CLI-entrypoint
```

Frameworket installeras via Composer och ligger normalt under:

```text
vendor/mattablues/radix-framework
```

---

## Huvudprinciper

Radix App är byggd kring några enkla principer:

1. **Tunn entrypoint**
   - `public/index.php` tar emot web requests.
   - `radix` tar emot CLI requests.

2. **Bootstrap först**
   - appen initieras via bootstrap-filer
   - konfiguration laddas
   - container skapas och konfigureras

3. **Konfiguration i `config/`**
   - appens beteende styrs via tydliga config-filer

4. **Appkod i `src/`**
   - controllers
   - middleware
   - services
   - providers
   - models
   - events/listeners
   - console commands

5. **Frameworkkod i Composer-paket**
   - generell funktionalitet kommer från `mattablues/radix-framework`
   - appen ska inte kopiera in framework-koden

6. **Funktionalitet kan läggas till via scaffolds**
   - scaffolds kan lägga till routes, controllers, views, migrations och config

---

## Request-cykeln för web

Ett normalt web request går ungefär så här:

```text
Browser
  ↓
public/index.php
  ↓
bootstrap
  ↓
container + config
  ↓
request object
  ↓
middleware
  ↓
router
  ↓
controller / handler
  ↓
response
  ↓
Browser
```

---

## 1. Entry point

Alla webbanrop ska normalt gå via:

```text
public/index.php
```

Rekommenderat är att webbserverns document root pekar på:

```text
public/
```

Det gör att resten av appen inte exponeras direkt via webben.

För enklare webbhotell där document root inte kan ändras kan projektets root-`.htaccess` skicka requests vidare till `public/`.

Se även:

- [`INSTALLATION.md`](INSTALLATION.md)
- [`SECURITY.md`](SECURITY.md)

---

## 2. Bootstrap

Bootstrap-steget ansvarar för att starta appen.

Typiskt sker här:

- path-konfiguration
- autoloading via Composer
- laddning av `.env`
- laddning av config
- skapande av container
- registrering av service providers
- förberedelse av router, middleware, sessions och andra core-delar

Bootstrap-koden ligger normalt under:

```text
bootstrap/
```

---

## 3. Config

Appens konfiguration ligger under:

```text
config/
```

Vanliga config-områden:

```text
app
database
services
providers
middleware
commands
routes
view/templates
mail
logging
cache
```

Exakt vilka filer som finns beror på starter-version och installerade scaffolds.

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## 4. Container & services

Radix använder en dependency injection-container.

Containern används för att:

- registrera services
- lösa dependencies
- skapa controllers
- skapa middleware
- registrera framework- och appkomponenter
- dela konfiguration och objekt på ett kontrollerat sätt

Appens egna services läggs normalt i:

```text
src/Services/
```

Service providers läggs normalt i:

```text
src/Providers/
```

Se även:

- [`SERVICES.md`](SERVICES.md)

---

## 5. Service providers

Service providers används för att samla bootstrapping och wiring för en viss del av appen.

En provider kan till exempel:

- registrera services i containern
- konfigurera listeners
- registrera integrationskod
- koppla ihop appens delar med frameworket

Provider-registrering sker normalt via config, till exempel:

```text
config/providers.php
```

Se även:

- [`SERVICES.md`](SERVICES.md)
- [`EVENTS.md`](EVENTS.md)

---

## 6. Middleware

Middleware körs före och/eller efter route-handlern.

Middleware kan användas för till exempel:

- sessions
- autentisering
- CSRF-skydd
- security headers
- rate limiting
- redirects
- request logging
- API-policy
- access control

Middleware kan vara global eller kopplad till specifika routes/grupper.

Appens middleware ligger normalt i:

```text
src/Middleware/
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## 7. Routing

Routern matchar:

```text
HTTP method + path
```

mot en handler.

Routes ligger normalt i:

```text
routes/
```

Exempel på route-filer kan vara:

```text
routes/web.php
routes/auth.php
routes/api.php
routes/admin.php
```

Vilka route-filer som finns beror på appens startläge och installerade scaffolds.

En route kan peka på:

- en closure/callable
- en controller-metod
- en invokable controller

Se mer i:

- [`ROUTING.md`](ROUTING.md)

---

## 8. Controllers och handlers

Controllers ligger normalt i:

```text
src/Controllers/
```

eller en liknande app-specifik namespace-struktur.

Controllers bör hålla sig relativt tunna och delegera tyngre logik till:

- services
- repositories/query objects
- models
- form requests/validators
- events/listeners

Se mer i:

- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## 9. Response

En controller eller handler returnerar normalt ett response-objekt.

För webbsidor är det vanligt att returnera HTML via templates.

För API:er är det vanligt att returnera JSON.

Se mer i:

- [`HTTP.md`](HTTP.md)
- [`API.md`](API.md)

---

## Template-rendering

Radix App använder Ratio templates.

Views ligger normalt i:

```text
views/
```

Templates använder filändelsen:

```text
.ratio.php
```

Template-systemet stödjer bland annat:

- layouts
- sections/blocks
- yield
- komponenter
- slots
- props
- escaping
- cache av kompilerade templates

Exempel på struktur:

```text
views/
  layouts/
  pages/
  components/
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)
- [`FRONTEND.md`](FRONTEND.md)

---

## Databas, migrationer och ORM

Radix Framework innehåller stöd för:

- databasanslutningar
- query builder
- migrations
- seeders
- enkel ORM/modellering

Appens databasfiler ligger normalt under:

```text
database/
```

Exempel:

```text
database/
  migrations/
  seeders/
```

Modeller ligger normalt i:

```text
src/Models/
```

Efter att nya migrations lagts till kör du:

```bash
php radix migrations:migrate
```

Se mer i:

- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)

---

## CLI-arkitektur

Radix App har en CLI-entrypoint:

```text
radix
```

Den körs med:

```bash
php radix
```

CLI:t används för:

- app setup
- migrationer
- seeders
- cache
- scaffolds
- generatorer
- app-kommandon

Frameworket innehåller generella console-komponenter och kommandon.

Appen kan även registrera egna kommandon via config, till exempel:

```text
config/commands.php
```

App-specifika kommandon ligger normalt i:

```text
src/Console/Commands/
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Scaffolds som arkitekturverktyg

Radix App kan byggas stegvis via scaffolds.

Ett scaffold kan lägga till till exempel:

```text
routes
controllers
middleware
views
config
migrations
seeders
services
events/listeners
frontend-filer
```

Det gör att starter-appen kan hållas minimal, samtidigt som större funktionalitet kan installeras vid behov.

Vanligt flöde:

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

För alla top-level presets:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Placeholder-filer

En minimal Radix App kan innehålla placeholder-filer.

Syftet är att appen ska vara:

- körbar
- statiskt analyserbar
- enkel att installera stegvis

Placeholder-filer kan senare ersättas av scaffolds.

Rekommenderat i en ny app:

```bash
php radix scaffold:install auth --force-placeholders
```

eller:

```bash
php radix scaffold:install --all --force-placeholders
```

Använd `--force` endast när du medvetet vill skriva över riktiga filer.

---

## Frontend och assets

Frontend-källor ligger normalt under:

```text
resources/
```

Exempel:

```text
resources/
  js/
  tailwind/
```

Publika assets ligger under:

```text
public/assets/
```

Uploads ligger separat:

```text
public/uploads/
```

Det är viktigt att skilja på:

```text
public/assets  = appens betrodda frontend-assets
public/uploads = användargenererade filer
```

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)

---

## Events och listeners

Radix har stöd för events och listeners.

De används för att frikoppla delar av appen.

Exempel på användning:

- skicka mail efter registrering
- logga viktiga händelser
- uppdatera statistik
- trigga bakgrundsjobb eller sync-flöden

Appens events och listeners ligger normalt i:

```text
src/Events/
src/Listeners/
```

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## Säkerhet som arkitekturprincip

Några viktiga arkitekturval:

- webroot bör vara `public/`
- appkod ska inte exponeras direkt
- uploads ska hållas separerade från app-assets
- middleware används för auth, CSRF och headers
- config/secrets ligger i `.env`
- debug ska vara av i produktion
- användargenererad input ska valideras

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Testbarhet

Radix App är tänkt att kunna testas med PHPUnit.

Tester ligger normalt under:

```text
tests/
```

Vanliga kommandon:

```bash
composer test
composer stan
composer format:check
```

Se mer i:

- [`TESTING.md`](TESTING.md)

---

## Sammanfattning

Radix App är uppdelad så här:

```text
public/index.php  tar emot web requests
radix             tar emot CLI requests
bootstrap/        startar appen
config/           styr appens beteende
routes/           definierar HTTP routes
src/              innehåller appkod
views/            innehåller templates
database/         innehåller migrations/seeders
resources/        innehåller frontend-källor
public/assets/    innehåller publika app-assets
public/uploads/   innehåller användaruppladdningar
vendor/           innehåller Composer-dependencies, inklusive Radix Framework
```

Och relationen mellan app och framework:

```text
Radix App
  använder
    ↓
Radix Framework
```

Radix App ska vara den plats där du bygger din applikation.

Radix Framework ska vara det återanvändbara Composer-paketet som ger appen dess grundfunktioner.
