# Radix CLI

## Usage

```bash
php radix <command> [arguments]
```

## Tillgängliga kommandon
- `migrations:migrate`
- `migrations:rollback`
- `seeds:run`
- `seeds:rollback`
- `make:migration`
- `make:seeder`
- `make:model`
- `make:controller`
- `make:event`
- `make:listener`
- `make:middleware`
- `make:service`
- `make:provider`
- `make:test`
- `make:view`
- `cache:clear`
- `app:setup`

## Examples

```bash
php radix make:view --help
php radix make:view --help --md
php radix migrations:rollback --help --md
```

### Exporting help to Markdown (Windows/PowerShell)

Om du kör i PowerShell på Windows, använd följande kommando för att säkerställa att teckenkodningen (UTF-8) blir korrekt i den exporterade filen:

```powershell
$OutputEncoding = [System.Text.Encoding]::UTF8
php radix --md | Out-File -FilePath docs/CLI.md -Encoding utf8
```

## Tips

- Kör `php radix <command> --help` för kommandospecifik hjälp.
- Kör `php radix --md` för att få denna hjälp som Markdown.