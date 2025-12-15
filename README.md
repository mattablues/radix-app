# Radix Framework
<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
<!-- doctoc will insert TOC here -->

- [Översikt](#översikt)
- [Komponenter](#komponenter)
- [Dokumentation](#dokumentation)
- [Installation](#installation)
- [Snabbstart (ORM – kort)](#snabbstart-orm--kort)
- [Utveckling & test](#utveckling--test)
- [Licens](#licens)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Översikt
Radix Framework är ett litet PHP 8.3-framework med “batteries included”: routing, middleware, templating, validering, fil-hantering och ett lättvikts ORM/QueryBuilder.

## Komponenter
- Routing + dispatcher (controllers/callables)
- Middleware-pipeline
- HTTP: Request/Response, JsonResponse, redirects
- Template/view-system
- Validering + helpers
- Fil: Reader/Writer/Upload
- Middleware: rate limiting, logging, security headers (om aktiverat)
- ORM/QueryBuilder: models, relations, pagination, soft deletes, eager loading
- CI: PHPUnit, PHPStan, format-check, valfritt Infection + schedule

## Dokumentation
- Docs-index: `docs/INDEX.md`
- ORM / QueryBuilder (full guide): `docs/ORM.md`
- CI / GitHub Actions:
  - `github-settings/CI_VARIABLES.md`
  - `github-settings/CI_TEMPLATE_SETUP.md`

## Installation
- Kräver PHP 8.3+, PDO (MySQL/SQLite m.fl.)
- Installera dependencies via Composer.
- Konfigurera databaskoppling i din container/DatabaseManager.
- Kör migrationer via ditt migrationssystem.

## Snabbstart (ORM – kort)

```php
<?php

use App\Models\User;

$users = User::query()
    ->select(['id', 'name'])
    ->where('status', '=', 'active')
    ->orderBy('name')
    ->limit(10)
    ->offset(0)
    ->get();

$email = User::query()
    ->where('id', '=', 1)
    ->value('email');

$emails = User::query()
    ->where('status', '=', 'active')
    ->pluck('email');
```

## Utveckling & test

```powershell
composer install
composer format:check
composer stan
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```

## Licens
MIT