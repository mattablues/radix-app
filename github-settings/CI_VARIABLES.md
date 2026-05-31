# CI-variabler (GitHub Actions)

← [`Till docs/index`](../docs/INDEX.md)

Det här repot använder GitHub Actions repo-variabler för att styra vilka delar av CI som körs.

Variablerna är **repository variables**, inte secrets.

> Secrets används för känsliga värden.  
> Dessa CI-flaggor är inte känsliga och ska ligga som Variables.

---

## Viktigt om körbara kommandon i den här filen

Den här filen ligger i:

```text
github-settings/
```

Om du klickar på **Run/Kör** i PhpStorm direkt från den här markdown-filen körs kommandot ofta med arbetskatalogen:

```text
github-settings/
```

Där finns ingen `composer.json`.

Därför använder körbara PowerShell-exempel i den här filen oftast:

```powershell
composer -d ..
```

Det betyder: kör Composer med projektroten som working directory.

Exempel:

```powershell
composer -d .. test
```

är samma sak som att först göra:

```powershell
cd ..
composer test
```

För npm används motsvarande:

```powershell
npm --prefix ..
```

---

## Var sätter man dem?

GitHub → **Settings** → **Secrets and variables** → **Actions** → fliken **Variables** → **New repository variable**.

---

## Variabler

| Variabel | `0` | `1` | Rekommenderad start |
|---|---|---|---|
| `ENABLE_FRONTEND_BUILD` | Hoppa över Node/npm build | Kör Node/npm build | `1` om projektet har frontend |
| `ENABLE_INFECTION_ON_PR` | Kör inte Infection på PR | Kör Infection på PR när path-filter matchar | `0` i början, `1` när testsviten är stabil |
| `ENABLE_INFECTION_ON_CI_CHANGES` | CI-ändringar triggar inte Infection | CI-ändringar kan trigga Infection | `0` |
| `ENABLE_INFECTION_ON_PUSH_MAIN` | Kör inte Infection på push till main | Kör Infection på push till main om workflow-villkoret matchar | `0` |
| `ENABLE_INFECTION_SCHEDULE` | Kör inte schemalagd Infection | Kör schemalagd Infection via cron | `0` |

---

## Rekommenderad start

För ett nytt Radix App-projekt rekommenderas:

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=0
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

Det ger snabbare PR:ar i början.

När testsviten är stabil kan du slå på Infection på PR:

```text
ENABLE_INFECTION_ON_PR=1
```

---

## Presets

### Preset A: Snabbt men frontend på

Rekommenderas för nytt projekt med frontend.

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=0
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

### Preset B: Striktare PR-kontroll

När testsviten är stabil.

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=1
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

### Preset C: Schemalagd mutation testing

Bra som kvalitetsbarometer utan att göra alla PR:ar långsamma.

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=0
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=1
```

### Extra strikt

Om ni vill köra Infection även vid push till main:

```text
ENABLE_INFECTION_ON_PUSH_MAIN=1
```

Det kan göra CI långsammare.

---

## Path-filter för Infection

Infection på PR styrs normalt både av variabler och path-filter.

Det betyder att Infection bara körs när:

```text
ENABLE_INFECTION_ON_PR=1
```

och PR:n ändrar relevanta filer, till exempel:

```text
src/**
tests/**
composer.*
phpunit.xml*
phpstan.neon*
infection.json*
```

Vissa workflows kan också innehålla filter som:

```text
framework/src/**
```

Det är främst relevant om samma workflow återanvänds i ett framework-repo eller monorepo. I ett vanligt Radix App-repo ligger frameworket via Composer och appens kod finns normalt i `src/`.

---

## Varför kör Infection inte på min PR?

Vanliga orsaker:

1. `ENABLE_INFECTION_ON_PR=0`
2. PR:n ändrar bara dokumentation
3. PR:n ändrar bara filer utanför path-filtret
4. Workflowet har skip-logik för CI-only changes
5. Infection-jobbet är manuellt/schemalagt i aktuell setup

Exempel på dokumentationsändring som normalt inte behöver Infection:

```text
README.md
docs/**
github-settings/**
```

---

## Varför kör Infection ändå ibland när jag bara ändrat CI-filer?

Om workflowet räknar `.github/workflows/**` eller `tools/**` som CI-ändringar kan Infection köras om:

```text
ENABLE_INFECTION_ON_CI_CHANGES=1
```

Rekommenderad start är:

```text
ENABLE_INFECTION_ON_CI_CHANGES=0
```

---

## Schedule-Infection

Schemalagd Infection körs via separat workflow om projektet har ett sådant, till exempel:

```text
.github/workflows/infection-schedule.yml
```

Om du vill aktivera cron-körningar:

```text
ENABLE_INFECTION_SCHEDULE=1
```

Om du vill stänga av dem:

```text
ENABLE_INFECTION_SCHEDULE=0
```

Manuell körning via GitHub UI kan fortfarande vara möjlig även när schedule är avstängt, beroende på workflow.

---

## Frontend build

Om projektet har frontend-assets bör denna vara på:

```text
ENABLE_FRONTEND_BUILD=1
```

Då kan CI köra Node/npm build.

I Radix App används normalt detta i CI:

```bash
npm ci
npm run start:build
```

Lokalt från projektroten kan du använda:

```bash
npm install
npm run start:build
```

Om du klickar **Run/Kör** från den här filen och terminalen står i `github-settings/`, använd:

```powershell
npm --prefix .. install
npm --prefix .. run start:build
```

eller CI-liknande:

```powershell
npm --prefix .. ci
npm --prefix .. run start:build
```

Se mer:

- [`../docs/FRONTEND.md`](../docs/FRONTEND.md)

---

## Checklista innan du öppnar en PR

### 1. Se vad din PR ändrar

PowerShell:

```powershell
git fetch origin
git diff --name-only origin/main...HEAD
```

Bash:

```bash
git fetch origin
git diff --name-only origin/main...HEAD
```

Om du ser filer under:

```text
src/
tests/
```

kan Infection köras om `ENABLE_INFECTION_ON_PR=1`.

---

## Kör samma grundchecks lokalt

### PowerShell från projektroten

```powershell
composer install
composer format:check
composer stan
composer test
```

### PowerShell från `github-settings/`

```powershell
composer -d .. install
composer -d .. format:check
composer -d .. stan
composer -d .. test
```

### Bash från projektroten

```bash
composer install
composer format:check
composer stan
composer test
```

Om frontend är aktiverad från projektroten:

```bash
npm install
npm run start:build
```

Om frontend är aktiverad och du kör från `github-settings/`:

```powershell
npm --prefix .. install
npm --prefix .. run start:build
```

---

## Kör PHPUnit direkt

Från projektroten:

```bash
vendor/bin/phpunit -c phpunit.xml --display-deprecations --display-errors --display-notices --do-not-cache-result
```

Från `github-settings/` i PowerShell:

```powershell
..\vendor\bin\phpunit -c ..\phpunit.xml --display-deprecations --display-errors --display-notices --do-not-cache-result
```

---

## Infection lokalt

I det här projektet ska du normalt köra Infection via ett av de specifika Composer-scripten:

```bash
composer infect:pcov
```

eller:

```bash
composer infect:xdebug
```

> Använd inte `composer infect` här om scriptet kräver mode.  
> Om du får `Usage: php tools/infection.php pcov|xdebug` betyder det att du ska köra `infect:pcov` eller `infect:xdebug`.

### Från projektroten

```bash
composer infect:pcov
```

eller:

```bash
composer infect:xdebug
```

### Från `github-settings/`

```powershell
composer -d .. infect:pcov
```

eller:

```powershell
composer -d .. infect:xdebug
```

### Snabbare felsökning mot en viss fil

Från projektroten:

```bash
vendor/bin/infection --configuration=infection.json.dist --threads=1 --show-mutations --filter="src/Path/To/File.php"
```

Från `github-settings/` i PowerShell:

```powershell
..\vendor\bin\infection --configuration=..\infection.json.dist --threads=1 --show-mutations --filter="..\src\Path\To\File.php"
```

---

## Cache / städa upp

Om PHPUnit, PHPStan eller Infection beter sig konstigt kan du rensa lokala caches.

### PowerShell från projektroten

```powershell
Remove-Item -Recurse -Force .phpunit.cache, build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force .phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force .infection.cache* -ErrorAction SilentlyContinue
composer dump-autoload -o
```

### PowerShell från `github-settings/`

```powershell
Remove-Item -Recurse -Force ..\.phpunit.cache, ..\build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force ..\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force ..\vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force ..\.infection.cache* -ErrorAction SilentlyContinue
composer -d .. dump-autoload -o
```

### Bash från projektroten

```bash
rm -rf .phpunit.cache build/coverage
rm -f .phpunit.result.cache
rm -f vendor/bin/.phpunit.result.cache
rm -f .infection.cache*
composer dump-autoload -o
```

Kör om utan PHPUnit-cache från projektroten:

```bash
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```

Från `github-settings/` i PowerShell:

```powershell
..\vendor\bin\phpunit -c ..\phpunit.xml --do-not-cache-result
```

---

## Windows temp-problem

Om Infection eller rate limit-tester strular på Windows på grund av temp-mappar:

```powershell
Remove-Item -Recurse -Force "$env:TEMP\radix_ratelimit" -ErrorAction SilentlyContinue
```

---

## Tips för stabila tester i CI/Infection

- undvik att bero på exakt sekundtid
- undvik långa `sleep()`
- använd loop med deadline om du måste vänta
- mocka externa tjänster
- använd temporära kataloger för filer/cache/loggar
- städa upp i `tearDown()`
- undvik riktiga nätverksanrop i unit tests
- använd deterministiska asserts
- håll tester isolerade från varandra

Se mer:

- [`../docs/TESTING.md`](../docs/TESTING.md)
