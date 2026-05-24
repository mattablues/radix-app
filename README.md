# README.md

# Radix App

Radix App är en **starter-applikation** för Radix som skapas via `composer create-project`.  
Själva ramverket lever som ett separat Composer-paket: `mattablues/radix-framework`.

> Den här repot är alltså “appen”, inte frameworket.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
<!-- doctoc will insert TOC here -->

- [Översikt](#%C3%B6versikt)
- [Installation](#installation)
- [Webroot och .htaccess](#webroot-och-htaccess)
- [Public assets och uploads](#public-assets-och-uploads)
- [Dokumentation](#dokumentation)
- [CLI (radix)](#cli-radix)
- [Scaffolds (lägga till funktionalitet)](#scaffolds-l%C3%A4gga-till-funktionalitet)
- [Utveckling & test](#utveckling--test)
- [Licens](#licens)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Översikt

Radix App är en färdig projektstruktur med routing, controllers, views/templates, CLI och en minimal starter-setup.

- Skapa projektet med `composer create-project`
- Kör `php radix app:setup` för grundsetup (migrations + ev. seeders)
- Lägg till mer funktionalitet stegvis via `scaffold:install ...` + `migrations:migrate`

## Installation

Skapa ett nytt projekt:

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
```

Installera frontend dependencies (om du ska bygga assets):

```bash
npm install
```

Grundsetup:

```bash
php radix app:setup
```

## Webroot och .htaccess

Rekommenderat är att peka serverns document root till `public/`.

För enklare webbhotell där document root inte kan ändras finns en `.htaccess` i projektroten som internt skickar requests vidare till `public/`.

Det finns även en `public/.htaccess` som hanterar webbroot-specifika Apache-regler, till exempel routing till `index.php`, skydd av dolda filer och cache headers.

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

`public/assets` innehåller appens betrodda frontend-assets, till exempel CSS, JavaScript, favicons, logotyper och statisk grafik.

`public/uploads` är reserverad för användargenererade filer. Den katalogen har striktare `.htaccess`-regler och ska inte användas för appens egna SVG-, JS- eller CSS-filer.

Exempel på app-assets:

```php
versioned_file('/assets/css/app.css');
versioned_file('/assets/js/app.js');
versioned_file('/assets/images/graphics/avatar.png');
```

Uppladdade avatarer sparas som exempelvis:

```text
public/uploads/users/1/avatar.jpg
```

och lagras normalt i databasen som publik path:

```text
/uploads/users/1/avatar.jpg
```

Default-avatar i Radix App ligger som app-asset:

```text
public/assets/images/graphics/avatar.png
```

med publik path:

```text
/assets/images/graphics/avatar.png
```

Denna struktur är en **Radix App-konvention**. Om du använder `mattablues/radix-framework` utan `radix-app` kan du själv välja asset- och upload-struktur. Frameworkets `versioned_file()` är generell och stödjer flera vanliga publika asset-kataloger, till exempel:

```text
/assets
/build
/dist
/css
/js
```

Det innebär:

```text
radix-app       = färdig rekommenderad struktur
radix-framework = flexibel grund där utvecklaren väljer själv
```

## Dokumentation

All dokumentation för appen finns under `docs/`.

👉 **[Radix App Documentation Index](docs/INDEX.md)**

## CLI (radix)

Kör CLI:

```bash
php radix [command] [arguments]
```

Se hela listan och vanliga flöden här:

- `docs/CLI.md`

## Scaffolds (lägga till funktionalitet)

Scaffolds är “paket” som lägger till filer + konfiguration + ev. migrations för ett steg (t.ex. `auth`, `user`, `admin`, `updates`).

Installera scaffold:

```bash
php radix scaffold:install <preset>
```

> Tips: I en ny app kan det finnas **placeholder-filer** (t.ex. tomma route-filer) för att verktyg som PHPStan ska vara nöjda direkt.  
> Om scaffold-installationen behöver skriva över sådana filer, använd i första hand `--force-placeholders`.

Rekommenderat när du installerar i en ny/ren app:

```bash
php radix scaffold:install <preset> --force-placeholders
```

Använd `--force` endast när du **medvetet vill skriva över allt** som krockar (t.ex. om du vill “återställa” filer till scaffoldets version):

```bash
php radix scaffold:install <preset> --force
```

Kör sedan migrations (scaffold kan lägga till nya migrationsfiler):

```bash
php radix migrations:migrate
```

## Utveckling & test

Vanliga kommandon (via Composer scripts):

```bash
composer format:check
composer stan
composer test
```

Valfritt (mutation testing):

```bash
composer infect:pcov
composer infect:xdebug
```

## Licens

MIT
