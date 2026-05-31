# docs/CLI.md

← [`Tillbaka till index`](INDEX.md)

# CLI (Radix App)

Radix App kommer med ett CLI-verktyg som körs via:

```bash
php radix
```

CLI:t används för bland annat:

- grundsetup av appen
- migrationer
- seeders
- cache-rensning
- scaffolds
- generatorer
- egna app-kommandon

> Vissa kommandon kommer från `mattablues/radix-framework`.  
> Andra kan vara app-specifika och registreras via appens konfiguration, till exempel `config/commands.php`.

---

## Usage

Visa kommandolistan:

```bash
php radix
```

Kör ett kommando:

```bash
php radix [command] [arguments]
```

Exempel:

```bash
php radix app:setup
php radix migrations:migrate
php radix scaffold:install auth --force-placeholders
```

---

## Hjälp

Visa hjälp för ett specifikt kommando:

```bash
php radix [command] --help
```

Exempel:

```bash
php radix scaffold:install --help
```

Visa hjälptext som Markdown:

```bash
php radix [command] --help --md
```

eller:

```bash
php radix [command] --help --markdown
```

Exempel:

```bash
php radix scaffold:install --help --md
```

Det är användbart när du vill kopiera hjälptext direkt till dokumentation.

---

## Tillgängliga kommandon

Exakt kommandolista kan variera beroende på appens konfiguration och installerade scaffolds.

Vanliga kommandon i Radix App:

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

Kontrollera alltid aktuell lista med:

```bash
php radix
```

---

## Snabbaste vägen till fungerande lokalt

För en ny app:

```bash
composer create-project mattablues/radix-app my-app
cd my-app
npm install
php radix app:setup
```

Om du vill installera scaffolds direkt:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

---

## `app:setup`

`app:setup` är grundsetup-kommandot för appen.

Kör:

```bash
php radix app:setup
```

Kommandot gör normalt följande:

- rensar cache
- kör migrationer
- kör seeders om det finns några

Det här är rekommenderat första kommando efter installation.

---

## Scaffolds

Scaffolds är paket med app-funktionalitet som kan lägga till filer och konfiguration i projektet.

Ett scaffold kan till exempel lägga till:

- routes
- controllers
- middleware
- views/templates
- config
- migrations
- seeders
- frontend-filer
- app-specifik kod

Exempel på presets kan vara:

```text
auth
user
admin
updates
routes/auth
```

Exakt vilka presets som finns beror på appens scaffold/preset-struktur.

---

## `scaffold:install`

### Usage

```bash
php radix scaffold:install <preset>|--all [--force] [--force-placeholders] [--dry-run]
```

### Argument

```text
<preset>
```

Namn eller path till preset under presets-root.

Exempel:

```bash
php radix scaffold:install auth
php radix scaffold:install routes/auth
```

---

## Scaffold-options

### `--all`

Installerar alla top-level presets och deras dependencies:

```bash
php radix scaffold:install --all
```

Rekommenderat i en ny app:

```bash
php radix scaffold:install --all --force-placeholders
```

### `--dry-run`

Visar vad som skulle installeras utan att skriva filer:

```bash
php radix scaffold:install auth --dry-run
```

Det är bra att använda innan du installerar ett scaffold i en app där du redan har ändrat filer.

### `--force-placeholders`

Skriver över endast placeholder-filer:

```bash
php radix scaffold:install auth --force-placeholders
```

Det här är rekommenderat i en ny/minimal app.

Placeholder-filer kan finnas för att statisk analys, till exempel PHPStan, ska vara nöjd innan full funktionalitet installerats.

Exempel på placeholder-markering:

```php
<?php

// RADIX_PLACEHOLDER
```

### `--force`

Skriver över befintliga filer vid konflikt:

```bash
php radix scaffold:install auth --force
```

Använd med försiktighet.

`--force` är rätt val när du medvetet vill ersätta befintliga filer med scaffoldets version, till exempel för att återställa scaffold-genererad kod.

### `--help`

Visar hjälp:

```bash
php radix scaffold:install --help
```

### `--md` / `--markdown`

Visar hjälp som Markdown:

```bash
php radix scaffold:install --help --md
```

---

## Scaffold-exempel

Installera ett scaffold:

```bash
php radix scaffold:install auth
```

Se vad som skulle hända utan att skriva filer:

```bash
php radix scaffold:install auth --dry-run
```

Installera och ersätt endast placeholder-filer:

```bash
php radix scaffold:install auth --force-placeholders
```

Installera ett nested preset:

```bash
php radix scaffold:install routes/auth --dry-run
```

Installera alla top-level presets:

```bash
php radix scaffold:install --all
```

Installera alla top-level presets och ersätt placeholder-filer:

```bash
php radix scaffold:install --all --force-placeholders
```

Skriv över befintliga filer vid konflikt:

```bash
php radix scaffold:install auth --force
```

---

## Rekommenderat scaffold-flöde

I en ny/minimal app:

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

För att installera allt:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

I en app där du redan har egen kod:

```bash
php radix scaffold:install auth --dry-run
```

Granska outputen innan du kör installationen på riktigt.

---

## Viktigt om placeholder-filer

En ny Radix App kan innehålla placeholder-filer.

Syftet är att projektet ska vara statiskt analyserbart och körbart även innan alla scaffolds är installerade.

När du installerar scaffolds vill du ofta ersätta placeholders, men inte råka skriva över riktiga filer.

Använd därför normalt:

```bash
php radix scaffold:install auth --force-placeholders
```

Undvik detta om du inte verkligen vill skriva över allt som krockar:

```bash
php radix scaffold:install auth --force
```

---

## Efter scaffold-installation

Scaffolds kan lägga till nya migrationsfiler.

Kör därför normalt:

```bash
php radix migrations:migrate
```

Om scaffoldet lägger till seeders och de inte redan körs av ditt flöde kan du även köra:

```bash
php radix seeds:run
```

---

## Migrationer

### Kör migrationer

```bash
php radix migrations:migrate
```

Det kör migrations som ännu inte har körts.

Vanligt efter:

- installation
- scaffold-installation
- ny migration skapad via generator

### Rollback

```bash
php radix migrations:rollback
```

Använd rollback med eftertanke, särskilt i miljöer där data är viktig.

---

## Seeders

### Kör seeders

```bash
php radix seeds:run
```

Seeders används för att lägga in startdata eller testdata.

### Rollback seeders

```bash
php radix seeds:rollback
```

Stöd och beteende kan bero på hur seeders är skrivna i appen.

---

## Cache

Rensa cache:

```bash
php radix cache:clear
```

Det är användbart efter ändringar i till exempel:

- config
- routes
- templates/views
- services
- CLI-registrering

---

## Generatorer (`make:*`)

Generatorerna skapar skelettfiler på rätt plats i projektet.

Vanliga generatorer:

```bash
php radix make:migration --help
php radix make:seeder --help
php radix make:model --help
php radix make:controller --help
php radix make:form-request --help
php radix make:event --help
php radix make:listener --help
php radix make:middleware --help
php radix make:service --help
php radix make:provider --help
php radix make:test --help
php radix make:view --help
```

Exempel:

```bash
php radix make:controller UserController
php radix make:model User
php radix make:migration create_users_table
php radix make:view users/index
php radix make:test UserTest
```

Kör alltid kommandots hjälp om du är osäker på argument och options:

```bash
php radix make:controller --help
```

---

## Egna CLI-kommandon (App Commands)

Radix App kan ha app-specifika CLI-kommandon.

I starter-appen finns ett kommando för att skapa egna app-kommandon:

```bash
php radix make:command <ClassName>
```

Det här är app-specifikt och skapar normalt kommandoklasser under:

```text
src/Console/Commands/
```

---

## Skapa ett nytt app-kommando

```bash
php radix make:command UsersSyncCommand
```

Exempel:

```bash
php radix make:command UsersSyncCommand
php radix make:command HealthCheckCommand --command=app:health
```

Som standard:

- klassen skapas under `src/Console/Commands/`
- CLI-namn härleds från klassnamnet om du inte anger `--command`
- kommandot registreras automatiskt i appens kommandokonfiguration om du inte använder `--no-config`

---

## Välja CLI-namn själv

Ange exakt kommando med:

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

Exempel:

```bash
php radix make:command HealthCheckCommand --command=app:health
```

---

## Automatisk namngivning för app-kommandon

Om du inte anger `--command` härleds CLI-namnet från klassnamnet.

Exempel:

```text
UsersSyncCommand      -> users:sync
HealthCheckCommand    -> health:check
CacheWarmupCommand    -> cache:warmup
```

För tydlighet rekommenderas ofta att ange CLI-namnet själv:

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

---

## Skippa automatisk registrering

Om du vill skapa filen men inte uppdatera konfigurationen automatiskt:

```bash
php radix make:command UsersSyncCommand --no-config
```

Då behöver du registrera kommandot manuellt i appens kommandokonfiguration, vanligtvis:

```text
config/commands.php
```

---

## Om ett kommando redan finns

`make:command` skyddar mot att registrera samma CLI-namn som redan finns.

Om du försöker skapa ett kommando med ett upptaget namn behöver du välja ett annat namn:

```bash
php radix make:command UsersSyncCommand --command=users:sync-v2
```

---

## Om ett kommando inte dyker upp

Kontrollera först att kommandot är registrerat i appens kommandokonfiguration.

Vanligtvis:

```text
config/commands.php
```

Rensa sedan cache:

```bash
php radix cache:clear
```

Visa kommandolistan igen:

```bash
php radix
```

---

## App-kommandon vs framework-kommandon

Radix App använder Radix Framework, men appen kan även registrera egna kommandon.

Förenklat:

```text
radix-framework = generella framework-kommandon
radix-app       = app-specifika kommandon och registrering
```

Exempel:

```text
scaffold:install      framework/app-integrerat scaffold-kommando
migrations:migrate    framework-kommando för migrationer
make:command          app-specifikt kommando i starter-appen
```

Det betyder att dokumentationen för CLI ska utgå från den faktiska appen du kör, inte bara frameworket isolerat.

---

## Vanliga flöden

### Ny app

```bash
composer create-project mattablues/radix-app my-app
cd my-app
npm install
php radix app:setup
```

### Ny app med scaffolds

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

### Installera ett enskilt scaffold

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

### Kontrollera innan scaffold-installation

```bash
php radix scaffold:install auth --dry-run
```

### Skapa controller och view

```bash
php radix make:controller PageController
php radix make:view pages/home
```

### Skapa migration och kör den

```bash
php radix make:migration create_posts_table
php radix migrations:migrate
```

### Skapa eget CLI-kommando

```bash
php radix make:command UsersSyncCommand --command=users:sync
php radix cache:clear
php radix
```

---

## Felsökning

### `php radix` fungerar inte

Kontrollera att du står i projektroten:

```bash
pwd
```

eller på Windows:

```bash
cd
```

Kontrollera att dependencies finns:

```bash
composer install
```

### Kommando saknas i listan

Rensa cache:

```bash
php radix cache:clear
```

Kontrollera appens kommandokonfiguration.

### Scaffold skriver inte över filer

Om filerna är placeholders:

```bash
php radix scaffold:install auth --force-placeholders
```

Om du medvetet vill skriva över riktiga filer:

```bash
php radix scaffold:install auth --force
```

### Scaffold ska inte skriva filer ännu

Använd:

```bash
php radix scaffold:install auth --dry-run
```

### Migrationer saknas efter scaffold

Kör:

```bash
php radix migrations:migrate
```

### Ändringar i config eller routes märks inte

Rensa cache:

```bash
php radix cache:clear
```

---

## Relaterat

- [`INSTALLATION.md`](INSTALLATION.md)
- [`CONFIG.md`](CONFIG.md)
- [`DATABASE.md`](DATABASE.md)
- [`ARCHITECTURE.md`](ARCHITECTURE.md)
- [`TESTING.md`](TESTING.md)
