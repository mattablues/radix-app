# docs/CLI.md

← [`Tillbaka till index`](INDEX.md)

# CLI (Radix App)

Radix App kommer med ett CLI-verktyg som körs via `php radix`.

---

## Usage

```bash
php radix [command] [arguments]
```

Tips:

- Kör `php radix [command] --help` för kommandospecifik hjälp
- Kör `php radix [command] --help --md` för att få hjälptexten som Markdown

---

## Tillgängliga kommandon

- `app:setup`
- `scaffold:install`
- `cache:clear`
- `migrations:migrate`
- `migrations:rollback`
- `seeds:run`
- `seeds:rollback`
- `make:migration`
- `make:seeder`
- `make:model`
- `make:controller`
- `make:form-request`
- `make:event`
- `make:listener`
- `make:middleware`
- `make:service`
- `make:provider`
- `make:test`
- `make:view`
- `make:command`

---

## Snabbaste vägen till “fungerande lokalt”

### 1) Grundsetup (starter)

Starter-projektet innehåller en minimal databas-setup (session-tabellen). Kör:

```bash
php radix app:setup
```

`app:setup`:
- rensar cache
- kör migrationer
- kör seeders (om det finns några)

---

## Scaffolds

Scaffolds är “paket” av app-funktionalitet (t.ex. `auth`, `user`, `admin`, `updates`) som lägger till det som behövs för varje steg: filer + ev. nya migrations.

### Usage

```bash
php radix scaffold:install <preset> [--force] [--dry-run]
```

### Options

- `<preset>` Namn eller path till preset under presets-root (t.ex. `auth`, `routes/auth`)
- `--force` Skriv över befintliga filer
- `--dry-run` Visa vad som skulle göras utan att skriva några filer
- `--help, -h` Visa hjälp för kommandot
- `--md, --markdown` Output hjälp som Markdown

### Examples

```bash
php radix scaffold:install auth
php radix scaffold:install user
php radix scaffold:install admin --force
php radix scaffold:install routes/auth --dry-run
```

### Viktigt: använd ofta `--force`

För att PHPStan inte ska klaga i en helt ny app kan starter-projektet innehålla vissa “tomma” filer (t.ex. route-filer).
När du installerar ett scaffold behöver du därför ofta `--force` för att scaffoldet ska kunna skriva över dessa filer.

Exempel:

```bash
php radix scaffold:install auth --force
```

### Efter scaffold-install: kör migrationer

Scaffolds kan lägga till nya migrationsfiler, så kör efter installation:

```bash
php radix migrations:migrate
```

---

## Migrationer

### Kör migrationer

```bash
php radix migrations:migrate
```

### Rollback (använd med eftertanke)

```bash
php radix migrations:rollback
```

---

## Seeders

### Kör seeders

```bash
php radix seeds:run
```

### Rollback seeders (om stöds i din setup)

```bash
php radix seeds:rollback
```

---

## Cache

Rensa cache (bra vid config-/template-ändringar):

```bash
php radix cache:clear
```

---

## Generatorer (make:*)

Generatorerna skapar skelettfiler på rätt plats i projektet. Se respektive kommando:

```bash
php radix make:controller --help
php radix make:model --help
php radix make:migration --help
php radix make:view --help
```

---

## Egna CLI-kommandon (App Commands)

Du kan skapa egna kommandon för din app och få dem automatiskt synliga i `php radix` (dvs. de hamnar i kommandolistan när de är registrerade).

### Skapa ett nytt kommando

```bash
php radix make:command <ClassName>
```

Exempel:

```bash
php radix make:command UsersSyncCommand
php radix make:command HealthCheckCommand --command=app:health
```

Som standard:

- klassen skapas under `src/Console/Commands/`
- kommandot registreras automatiskt via appens kommandokonfiguration (om du inte stänger av det)

### Välja kommandonamn själv

Du kan ange exakt CLI-namn med `--command=...`:

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

### Skippa automatisk registrering (manuell registrering)

Om du vill skapa filen men *inte* uppdatera konfigurationen automatiskt:

```bash
php radix make:command UsersSyncCommand --no-config
```

Då behöver du registrera kommandot manuellt i appens kommandokonfiguration (vanligtvis i `config/commands.php`).

### Om ett kommando inte dyker upp

1) Säkerställ att kommandot är registrerat i konfigen  
2) Kör cache-rensning om du har config/CLI-cache:

```bash
php radix cache:clear
```

3) Kör `php radix` igen och kontrollera listan
