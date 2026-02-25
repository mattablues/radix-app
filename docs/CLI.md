# Radix CLI

## Usage

```bash
php radix <command> [arguments]
```

## Tillgängliga kommandon

> Tips: Kör `php radix <command> --help` för kommandospecifik hjälp.  
> Tips: Kör `php radix <command> --help --md` för att få hjälptexten som Markdown.

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

## Production-säkerhet (rekommenderad policy)

Det här projektet är designat så att CLI i production kan begränsas med en **allowlist** (för att undvika misstag och farliga körningar).

### RADIX_DEPLOY (deploy-läge)

Vissa kommandon kan vara tillåtna i production **endast** när du medvetet “armar” deploy-läge genom att sätta miljövariabeln:

- `RADIX_DEPLOY=1`

Rekommendation: sätt den **per körning** (inte permanent i `.env`), så att du inte råkar lämna deploy-läge påslaget.

#### Exempel (Linux/macOS, bash/sh)

```bash
RADIX_DEPLOY=1 php radix migrations:migrate
```

#### Exempel (Windows CMD)

```bat
set RADIX_DEPLOY=1 && php radix migrations:migrate
```

#### Exempel (Windows PowerShell)

```powershell
$env:RADIX_DEPLOY="1"; php radix migrations:migrate; Remove-Item Env:RADIX_DEPLOY
```

### Rekommenderat i production

**Tillåt alltid:**
- `cache:clear`

**Tillåt endast i deploy-läge (`RADIX_DEPLOY=1`):**
- `migrations:migrate`

**Blocka i production (rekommenderat):**
- `app:setup` (kör dev/demo-seeders och är inte avsett för production)
- `migrations:rollback` (risk för dataförlust/inkonsistens)
- `seeds:*` (dev/demo)
- `scaffold:*` (dev/CI)
- `make:*` (kodgeneratorer)

---

## Sessions: file vs database (viktigt)

Projektet stödjer sessions via både `file` och `database`.

### Rekommenderat flöde för database sessions

1) Börja med:
```dotenv
SESSION_DRIVER=file
```

2) Skapa tabellen via migrations:
- Development:
```bash
php radix migrations:migrate
```

- Production:
```bash
RADIX_DEPLOY=1 php radix migrations:migrate
```

3) Byt sedan till:
```dotenv
SESSION_DRIVER=database
```

---

## Examples

```bash
php radix make:view --help
php radix make:view --help --md
php radix migrations:rollback --help --md
php radix scaffold:install --help --md
```

---

## Exporting help to Markdown

### Linux/macOS

```bash
php radix --md > docs/CLI.md
```

### Windows/PowerShell

Om du kör i PowerShell på Windows, använd följande kommando för att säkerställa att teckenkodningen (UTF-8) blir korrekt i den exporterade filen:

```powershell
$OutputEncoding = [System.Text.Encoding]::UTF8
php radix --md | Out-File -FilePath docs/CLI.md -Encoding utf8
```
