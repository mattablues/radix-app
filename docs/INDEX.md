# docs/INDEX.md

# Radix App — Dokumentation

Det här är dokumentationen för **Radix App**, alltså starter-applikationen som använder `mattablues/radix-framework` via Composer.

> **Radix App** är app-strukturen du bygger vidare på.  
> **Radix Framework** är själva ramverket som ligger i Composer-paketet `mattablues/radix-framework`.

---

## Start här

1. **Installation:** [`INSTALLATION.md`](INSTALLATION.md)
2. **CLI & scaffolds:** [`CLI.md`](CLI.md)
3. **Arkitektur:** [`ARCHITECTURE.md`](ARCHITECTURE.md)

---

## Översikt

Radix App innehåller en färdig projektstruktur för en Radix-baserad applikation:

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

Framework-koden ligger inte direkt i appen, utan installeras via Composer:

```text
vendor/mattablues/radix-framework
```

Det betyder att dokumentationen ibland skiljer på:

```text
radix-app       = starter-projektet / applikationen
radix-framework = själva ramverket
```

---

## Installation & setup

- **Installation:** [`INSTALLATION.md`](INSTALLATION.md)
- **Konfiguration:** [`CONFIG.md`](CONFIG.md)
- **CLI:** [`CLI.md`](CLI.md)

Vanligt startflöde:

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
npm install
php radix app:setup
```

---

## CLI & scaffolds

- **CLI-kommandon:** [`CLI.md`](CLI.md)
- **Scaffolds:** [`CLI.md#scaffolds`](CLI.md#scaffolds)
- **Generatorer:** [`CLI.md#generatorer-make`](CLI.md#generatorer-make)
- **Egna app-kommandon:** [`CLI.md#egna-cli-kommandon-app-commands`](CLI.md#egna-cli-kommandon-app-commands)

Scaffolds används för att lägga till funktionalitet stegvis i appen, till exempel routes, auth, user/admin-delar eller andra presets.

Exempel:

```bash
php radix scaffold:install auth --force-placeholders
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

> I en ny/minimal app kan vissa filer vara placeholder-filer för att statisk analys, till exempel PHPStan, ska vara nöjd.  
> Använd normalt `--force-placeholders` i en ny app, och använd bara `--force` när du medvetet vill skriva över riktiga filer.

---

## Appens byggstenar

- **Routing:** [`ROUTING.md`](ROUTING.md)
- **Controllers:** [`CONTROLLERS.md`](CONTROLLERS.md)
- **Middleware:** [`MIDDLEWARE.md`](MIDDLEWARE.md)
- **HTTP request/response:** [`HTTP.md`](HTTP.md)
- **Services & DI:** [`SERVICES.md`](SERVICES.md)
- **Events & listeners:** [`EVENTS.md`](EVENTS.md)

---

## Databas & modellering

- **Database & migrations:** [`DATABASE.md`](DATABASE.md)
- **ORM:** [`ORM.md`](ORM.md)
- **Validation:** [`VALIDATION.md`](VALIDATION.md)

Vanligt flöde efter nya migrations eller scaffolds:

```bash
php radix migrations:migrate
```

---

## Templates & frontend

- **Templates:** [`TEMPLATES.md`](TEMPLATES.md)
- **Frontend:** [`FRONTEND.md`](FRONTEND.md)
- **Images & uploads:** [`IMAGES.md`](IMAGES.md)
- **Files:** [`FILES.md`](FILES.md)

Radix App har en rekommenderad struktur för publika assets och uploads:

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

---

## Arkitektur & avancerat

- **Architecture:** [`ARCHITECTURE.md`](ARCHITECTURE.md)
- **Caching:** [`CACHE.md`](CACHE.md)
- **Logging:** [`LOGGING.md`](LOGGING.md)
- **Mail:** [`MAIL.md`](MAIL.md)
- **Security:** [`SECURITY.md`](SECURITY.md)
- **API:** [`API.md`](API.md)
- **Geo location:** [`GEOLOCATION.md`](GEOLOCATION.md)
- **Cookbook:** [`COOKBOOK.md`](COOKBOOK.md)

---

## Kvalitet & utveckling

- **Testing:** [`TESTING.md`](TESTING.md)

Vanliga kvalitetskommandon:

```bash
composer format:check
composer stan
composer test
```

Valfritt:

```bash
composer infect
composer infect:pcov
composer infect:xdebug
```

---

## CI (GitHub Actions)

- **CI template setup:** [`../github-settings/CI_TEMPLATE_SETUP.md`](../github-settings/CI_TEMPLATE_SETUP.md)
- **CI-variabler:** [`../github-settings/CI_VARIABLES.md`](../github-settings/CI_VARIABLES.md)

---

## Rekommenderad läsordning

Om du är ny i projektet:

1. [`INSTALLATION.md`](INSTALLATION.md)
2. [`CLI.md`](CLI.md)
3. [`ARCHITECTURE.md`](ARCHITECTURE.md)
4. [`CONFIG.md`](CONFIG.md)
5. [`ROUTING.md`](ROUTING.md)
6. [`CONTROLLERS.md`](CONTROLLERS.md)
7. [`TEMPLATES.md`](TEMPLATES.md)
8. [`DATABASE.md`](DATABASE.md)
9. [`TESTING.md`](TESTING.md)
