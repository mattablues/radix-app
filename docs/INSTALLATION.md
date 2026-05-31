# docs/INSTALLATION.md

← [`Tillbaka till index`](INDEX.md)

# Installation (Radix App)

Den här guiden gäller **Radix App**, alltså starter-projektet som skapas via:

```bash
composer create-project mattablues/radix-app <din-app>
```

Radix App använder själva ramverket via Composer-paketet:

```text
mattablues/radix-framework
```

> Du ska normalt inte checka in framework-kod i app-repot.  
> Appen innehåller din projektstruktur, konfiguration, routes, controllers, views, assets och app-specifik kod.

---

## Krav

- PHP **8.3**
- Composer
- Node.js + npm
- Databas om du använder migrationer, ORM, sessions i databas eller scaffolds som kräver tabeller
- Rekommenderat: en webbserver som kan peka document root till `public/`

PHP-dependencies installeras med Composer.

Frontend-dependencies installeras med npm.

---

## 1) Skapa projektet

Skapa ett nytt Radix App-projekt:

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
```

Exempel:

```bash
composer create-project mattablues/radix-app my-app
cd my-app
```

Efter `create-project` kör appens Composer-hook normalt en miljö-bootstrap som skapar eller uppdaterar `.env` och genererar secrets vid behov.

Om du senare kör `composer install` eller `composer update` körs samma bootstrap igen.

---

## 2) Installera dependencies

I normalfallet är PHP-dependencies redan installerade efter `composer create-project`.

Om du behöver installera om dem:

```bash
composer install
```

Installera frontend-dependencies:

```bash
npm install
```

---

## 3) Konfigurera `.env`

Kontrollera att `.env` finns och innehåller rätt värden för din lokala miljö.

Vanliga saker att kontrollera:

```text
APP_ENV
APP_DEBUG
APP_URL

DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

SESSION_DRIVER
```

Exakta nycklar beror på appens `config/`-filer och vilka scaffolds du installerar.

Se även:

- [`CONFIG.md`](CONFIG.md)
- [`DATABASE.md`](DATABASE.md)

---

## 4) Webroot

Rekommenderat är att peka webbserverns document root till:

```text
public/
```

Exempel:

```text
/path/to/my-app/public
```

Radix App har även stöd för enklare webbhotell där document root inte kan ändras. Då kan `.htaccess` i projektroten skicka requests vidare till `public/`.

Det finns också en `public/.htaccess` för webbroot-specifika Apache-regler, till exempel:

- routing till `index.php`
- skydd av dolda filer
- cache headers
- separata regler för uploads

---

## 5) Grundsetup via CLI

Kör appens grundsetup:

```bash
php radix app:setup
```

`app:setup` gör normalt följande:

- rensar cache
- kör migrationer
- kör seeders om det finns några

Det här är det rekommenderade första kommandot efter installation.

---

## 6) Kontrollera att CLI fungerar

Visa tillgängliga CLI-kommandon:

```bash
php radix
```

Visa hjälp för ett specifikt kommando:

```bash
php radix app:setup --help
```

Du kan även få hjälptext i Markdown-format:

```bash
php radix app:setup --help --md
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## 7) Lägga till funktionalitet via scaffolds

Radix App börjar som en starter-applikation. Mer funktionalitet kan läggas till stegvis via scaffolds.

Ett scaffold kan lägga till till exempel:

- routes
- controllers
- views/templates
- config
- migrations
- seeders
- app-kod

Installera ett scaffold:

```bash
php radix scaffold:install <preset>
```

Exempel:

```bash
php radix scaffold:install auth
```

Efter scaffold-installation ska du normalt köra migrationer:

```bash
php radix migrations:migrate
```

---

## 8) Rekommenderat i ny/minimal app

En ny/minimal Radix App kan innehålla placeholder-filer för att statisk analys, till exempel PHPStan, ska vara nöjd redan innan alla scaffolds är installerade.

När ett scaffold behöver ersätta sådana placeholder-filer bör du använda:

```bash
php radix scaffold:install auth --force-placeholders
```

För att installera alla tillgängliga top-level presets:

```bash
php radix scaffold:install --all --force-placeholders
```

Kör sedan:

```bash
php radix migrations:migrate
```

---

## 9) `--dry-run`, `--force-placeholders` och `--force`

Scaffold-kommandot stödjer bland annat:

```bash
php radix scaffold:install <preset>|--all [--force] [--force-placeholders] [--dry-run]
```

### `--dry-run`

Visar vad som skulle installeras utan att skriva filer:

```bash
php radix scaffold:install auth --dry-run
```

Det är bra att köra först om du vill se vilka filer som påverkas.

### `--force-placeholders`

Skriver bara över filer som är markerade som placeholders:

```bash
php radix scaffold:install auth --force-placeholders
```

Det här är normalt säkrast i en ny app.

### `--force`

Skriver över befintliga filer vid konflikt:

```bash
php radix scaffold:install auth --force
```

Använd `--force` med försiktighet, eftersom det kan ersätta filer du redan har ändrat.

---

## 10) Bygga frontend-assets

Om appen använder frontend-build:

```bash
npm install
```

Vilka npm-script som finns beror på `package.json`.

Vanligt är att appen har resurser under:

```text
resources/
  js/
  tailwind/
```

och publika byggda filer under:

```text
public/assets/
```

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`IMAGES.md`](IMAGES.md)

---

## 11) Kvalitetskontroller

Vanliga Composer-kommandon under utveckling:

```bash
composer format:check
composer stan
composer test
```

Autoformatering:

```bash
composer format
```

Mutation testing, om du vill köra det:

```bash
composer infect
composer infect:pcov
composer infect:xdebug
```

Se mer i:

- [`TESTING.md`](TESTING.md)

---

## 12) Vanligt startflöde

Ett vanligt flöde för en ny app:

```bash
composer create-project mattablues/radix-app my-app
cd my-app
npm install
php radix app:setup
composer format:check
composer stan
composer test
```

Om du vill installera scaffolds direkt:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

---

## 13) Nästa steg

Läs vidare:

- [`CLI.md`](CLI.md)
- [`CONFIG.md`](CONFIG.md)
- [`ARCHITECTURE.md`](ARCHITECTURE.md)
- [`ROUTING.md`](ROUTING.md)
- [`DATABASE.md`](DATABASE.md)
- [`TESTING.md`](TESTING.md)
