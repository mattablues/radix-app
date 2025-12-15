# CLI – Contributing Guide (Help + Markdown)

Den här guiden beskriver hur du lägger till/uppdaterar CLI-kommandon i Radix så att de:
- har konsekvent `--help` / `-h`
- kan skriva help i Markdown med `--md` / `--markdown`
- kan generera uppdaterad `docs/CLI.md` korrekt i UTF‑8 (Windows/PowerShell)

## Mål

1. `php radix --md` ska skriva **global help** i Markdown (inkl. kommandolista och exempel).
2. `php radix <command> --help --md` ska skriva **kommandospecifik help** i Markdown.
3. Alla kommandon ska ha tydlig usage, options och gärna examples.

## Regler (följ strikt)

- Ändra en fil i taget när du jobbar med CLI-help.
- I varje kommando: kör help-hantering tidigt i `__invoke()` / `execute()`.
- Ta bort gammal speciallogik för `--help` när den blir överflödig.
- Efter varje ändring: testa både vanlig help och Markdown-help.

## Standardmallen för ett kommando

I din `__invoke(array $args): void`:

1) Definiera `usage`, `options`, och (valfritt) `examples`:

- `usage` ska vara **utan** `php radix`, t.ex. `make:view <path> ...` eller `cache:clear`.
- `options` ska alltid innehålla:
  - `--help, -h`
  - `--md, --markdown`
- `examples` ska vara kommandorader som går att köra (utan `php radix`), t.ex. `make:view about/index --layout=main`.

2) Anropa help tidigt:

```php
if ($this->handleHelpFlag($args, $usage, $options, $examples)) {
    return;
}
```

3) Argument-parsning:

- Om kommandot tar positionella argument: ignorera tokens som börjar med `-`
  så att flags (`--md`, `--help`) inte råkar tolkas som ett argument.

## Subcommands (t.ex. migrations:* / seeds:*)

Om du har ett “multi-command” som väljer beteende via `$_command`:

- Sätt `usage`, `options` och `examples` beroende på subcommand.
- Testa varje subcommand separat med `--help` och `--help --md`.

## Registrera kommandot

När du lägger till ett nytt kommando:

- Registrera kommandot i projektets command registry (så det dyker upp i global help).
- Verifiera att det syns i listan:

```bash
php radix --md
```

## Test-rutin (obligatorisk)

Efter varje CLI-ändring:

```bash
php radix <command> --help
php radix <command> --help --md
```

För subcommands:

```bash
php radix <sub:command> --help
php radix <sub:command> --help --md
```

## Generera docs/CLI.md (Windows / PowerShell)

För att undvika fel encoding (t.ex. trasiga å/ä/ö), använd alltid UTF‑8 vid export:

```powershell
php radix --md | Out-File -FilePath docs/CLI.md -Encoding utf8
```

Snabbkontroll:

```powershell
Get-Content docs/CLI.md -Encoding utf8 | Select-Object -First 120
```

## Kvalitetssäkring

När du är klar (eller innan du pushar):

- Kör PHPUnit
- Kör PHPStan

Målet är “grönt” på båda innan ändringen anses klar.

## Vanliga fallgropar

- Skriv inte `php radix ...` i kommandots `usage` (global help visar redan prefixet).
- Glöm inte `--markdown` (inte bara `--md`).
- Om kommandot inte syns i `php radix --md`: det är nästan alltid att det inte registrerats.

---
Klart: När kommandot har `--help` + `--help --md`, och `docs/CLI.md` är uppdaterad och korrekt i UTF‑8.
