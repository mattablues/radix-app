# README.md

# Radix App

Radix App är en **starter-applikation** för Radix som skapas via `composer create-project`.  
Själva ramverket lever som ett separat Composer-paket: `mattablues/radix-framework`.

> Den här repot är alltså “appen”, inte frameworket.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
<!-- doctoc will insert TOC here -->

- [Översikt](#översikt)
- [Installation](#installation)
- [Dokumentation](#dokumentation)
- [CLI (radix)](#cli-radix)
- [Scaffolds (lägga till funktionalitet)](#scaffolds-lägga-till-funktionalitet)
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
php radix scaffold:install <preset> --force
```

> `--force` behövs ofta eftersom starter-projektet kan ha “tomma” filer (t.ex. routes) för att hålla PHPStan nöjd.

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
