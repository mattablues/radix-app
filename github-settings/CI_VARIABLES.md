# CI-variabler (GitHub Actions)

Det här repot använder repo-variabler (inte secrets) för att styra vilka delar av CI som körs.

## Var sätter man dem?
GitHub → **Settings** → **Secrets and variables** → **Actions** → fliken **Variables** → **New repository variable**.

## Variabler

| Variabel | 0 | 1 | Rekommenderad start |
|---|---|---|---|
| `ENABLE_FRONTEND_BUILD` | Hoppa över Node/npm build | Kör Node/npm build | `1` om projektet har frontend |
| `ENABLE_INFECTION_ON_PR` | Kör inte Infection på PR | Kör Infection på PR (när core-filer ändras) | `0` i början, `1` när testsviten är stabil |
| `ENABLE_INFECTION_ON_CI_CHANGES` | CI-ändringar triggar inte Infection | CI-ändringar kan trigga Infection | `0` |
| `ENABLE_INFECTION_ON_PUSH_MAIN` | Kör inte Infection på push till main | Kör Infection på push till main (om workflow-villkoret matchar) | `0` |
| `ENABLE_INFECTION_SCHEDULE` | Kör inte schemalagd Infection | Kör schemalagd Infection (cron) | `0` |

**Obs:** Infection på PR styrs också av path-filter. Den kör bara när PR:n ändrar t.ex. `src/**`, `framework/src/**`, `tests/**` eller vissa configfiler.

## Presets

### Preset A: Snabbt men frontend på (rekommenderas för nytt projekt med frontend)

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=0
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

### Preset B: Strikt med frontend (när ni är redo att kvalitetssäkra hårdare)

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=1
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

### Extra strikt (valfritt)
Om ni vill ha ett “säkerhetsnät” som kör Infection även vid push till main:

```text
ENABLE_INFECTION_ON_PUSH_MAIN=1
```

Detta kan bli tungt och göra CI långsammare.

### Schedule (valfritt)
Om ni vill att Infection ska köras automatiskt på schema (cron) via `.github/workflows/infection-schedule.yml`:

```text
ENABLE_INFECTION_SCHEDULE=1
```

## Vanliga frågor / felsökning

### Varför kör Infection inte på min PR?
Vanligaste orsaken är att PR:n inte ändrar några filer som matchar path-filtret för “core”.

Infection kör på PR när:
- `ENABLE_INFECTION_ON_PR=1`, och
- PR:n ändrar t.ex. `src/**`, `framework/src/**`, `tests/**` eller vissa configfiler (`composer.*`, `phpunit.xml*`, `phpstan.neon*`, `infection.json*`).

Om du bara ändrar t.ex. `README.md` eller andra filer utanför dessa mappar så kommer Infection-jobbet skippa snabbt (det är medvetet).

### Varför kör Infection bara mot src/?
Som standard är CI konfigurerat att bara köra mutation testing mot applikationskoden (`src/`). 
Detta för att snabba upp CI och fokusera på koden du faktiskt jobbar med. Om du vill inkludera frameworket, ändra `INFECTION_FILTER` i workflow-filen till `""` (tom sträng).

### Varför kör Infection ändå ibland när jag bara ändrat CI-filer?
Om du ändrar `.github/workflows/**` eller `tools/**` räknas det som “CI-ändringar”. Då kör Infection bara om:
- `ENABLE_INFECTION_ON_CI_CHANGES=1`.

### Varför kör schedule-Infection även när min PR inte triggar det?
Schedule-Infection körs i en separat workflow: `.github/workflows/infection-schedule.yml`.

- Om `ENABLE_INFECTION_SCHEDULE=0` så skippar den schemalagda körningen (men du kan fortfarande köra manuellt via “Run workflow”).
- Om `ENABLE_INFECTION_SCHEDULE=1` så kör den automatiskt på cron.

### Varför tar CI lång tid på små PR:ar (t.ex. README)?
Även om Infection skippas kan `php`-jobbet fortfarande köra format/phpstan/phpunit. Det är normalt i den här setupen.
Om ni vill kan man senare lägga in path-filter även för `php`-jobbet, men det är ett medvetet avvägande (hastighet vs säkerhet).

## Checklista innan du öppnar en PR

### 1) Snabb koll: vad innehåller min PR?
Det här hjälper dig att förutse om Infection kommer köras:

```powershell
git fetch origin
git diff --name-only origin/main...HEAD
```

Ser du `src/`, `framework/src/` eller `tests/` i listan → räkna med Infection på PR (om `ENABLE_INFECTION_ON_PR=1`).

### 2) Kör samma grundchecks som CI (lokalt)

#### PowerShell (Windows)
Kör detta i projektroten:

```powershell
composer install
composer format:check
composer stan
vendor/bin/phpunit -c phpunit.xml --display-deprecations --display-errors --display-notices --do-not-cache-result
```

#### Bash (Linux/macOS)

```bash
composer install
composer format:check
composer stan
vendor/bin/phpunit -c phpunit.xml --display-deprecations --display-errors --display-notices --do-not-cache-result
```

### 3) Infection lokalt (när du ändrat “core”-filer)

#### Standard (via composer-script)

```powershell
composer infect
```

#### Kör Infection med filter (snabbare felsökning)

```powershell
vendor/bin/infection --configuration=infection.json.dist --threads=1 --show-mutations --filter="SÖKVÄG\TILL\FIL.php"
```

## Cache / “städa upp” (när CI eller Infection blir konstigt)

### PowerShell: rensa vanliga caches
Kör i projektroten:

```powershell
Remove-Item -Recurse -Force .phpunit.cache, build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force .phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force .infection.cache* -ErrorAction SilentlyContinue
```

### PowerShell: bygg om autoload (kan hjälpa efter stora ändringar)

```powershell
composer dump-autoload -o
```

### PowerShell: kör om deterministiskt utan PHPUnit-cache

```powershell
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```

### PowerShell: om Infection/coverage strular p.g.a. temp-mappar
Det här är ett “sista steg” om du får lås-/temp-problem på Windows:

```powershell
Remove-Item -Recurse -Force "$env:TEMP\radix_ratelimit" -ErrorAction SilentlyContinue
```

## Tips för stabila tester (särskilt i CI/Infection)
- Undvik att bero på exakt “nu” i sekunder om du kan (tid kan bli flakigt med Xdebug/Infection).
- När du måste testa defaultvärden/konstanter