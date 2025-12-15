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

## Examples

```bash
php radix make:view --help
php radix make:view --help --md
php radix migrations:rollback --help --md
php radix --md | Out-File -FilePath docs/CLI.md -Encoding utf8
```

## Tips

- Kör `php radix <command> --help` för kommandospecifik hjälp.
- Kör `php radix --md` för att få denna hjälp som Markdown.

