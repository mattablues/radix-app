# CI Template Setup

← [`Till docs/index`](../docs/INDEX.md)

Det här dokumentet är en checklista för att få GitHub Actions stabilt och förutsägbart i ett nytt repo som bygger på Radix App.

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

Därför använder vissa PowerShell-exempel i den här filen:

```powershell
composer -d ..
```

Det betyder: kör Composer från projektroten.

Exempel:

```powershell
composer -d .. test
```

är samma sak som:

```powershell
cd ..
composer test
```

För npm används:

```powershell
npm --prefix ..
```

---

## 0. Förutsättningar

Kontrollera att repot har:

```text
.github/workflows/ci.yml
composer.json
composer.lock
phpunit.xml
phpstan.neon.dist eller phpstan.minimal.neon
```

Om frontend används:

```text
package.json
package-lock.json
resources/
public/assets/
```

Projektet använder:

- PHP 8.3
- Composer
- PHPUnit
- PHPStan
- PHP-CS-Fixer
- npm
- valfritt: Infection

Se även:

- [`../docs/TESTING.md`](../docs/TESTING.md)
- [`../docs/FRONTEND.md`](../docs/FRONTEND.md)

---

## 1. Sätt GitHub Actions variables

Gå till:

```text
GitHub
  → Settings
  → Secrets and variables
  → Actions
  → Variables
  → New repository variable
```

Rekommenderad start:

```text
ENABLE_FRONTEND_BUILD=1
ENABLE_INFECTION_ON_PR=0
ENABLE_INFECTION_ON_CI_CHANGES=0
ENABLE_INFECTION_ON_PUSH_MAIN=0
ENABLE_INFECTION_SCHEDULE=0
```

Detta ger snabb och stabil CI i början.

När testsviten är stabil kan ni slå på Infection på PR:

```text
ENABLE_INFECTION_ON_PR=1
```

Om ni vill ha schemalagd mutation testing:

```text
ENABLE_INFECTION_SCHEDULE=1
```

Se mer:

- [`CI_VARIABLES.md`](CI_VARIABLES.md)

---

## 2. Kontrollera Actions permissions

Gå till:

```text
GitHub
  → Settings
  → Actions
  → General
```

Kontrollera:

- GitHub Actions är tillåtet
- workflow permissions är rätt för repot
- organisationens policy blockerar inte Actions

---

## 3. Branch protection

Gå till:

```text
GitHub
  → Settings
  → Branches
  → Add branch protection rule
```

Rekommenderat för `main`:

```text
Require a pull request before merging: ON
Require status checks to pass before merging: ON
Require branches to be up to date before merging: valfritt
Allow force pushes: OFF
Allow deletions: OFF
```

Välj till exempel CI-jobbet:

```text
CI / php
```

som required check.

Om Infection-jobbet görs required måste skip-läget avslutas med success, annars kan dokumentations-PR:ar blockeras.

---

## 4. Första körningen

Triggera workflow manuellt:

```text
GitHub
  → Actions
  → CI
  → Run workflow
```

Låt default vara enligt workflowets input, till exempel:

```text
run_infection_mode = schedule
```

Kontrollera att:

- Composer install fungerar
- PHPUnit körs
- PHPStan körs
- format check körs
- frontend build körs om `ENABLE_FRONTEND_BUILD=1`
- Infection skippar eller kör enligt variabler

---

## 5. Lokal setup

Kör samma grundchecks lokalt.

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

Om frontend finns från projektroten:

```bash
npm install
npm run start:build
```

Om frontend finns och du kör från `github-settings/`:

```powershell
npm --prefix .. install
npm --prefix .. run start:build
```

I CI används ofta:

```bash
npm ci
npm run start:build
```

Från `github-settings/` motsvaras det av:

```powershell
npm --prefix .. ci
npm --prefix .. run start:build
```

---

## 6. Vanliga första fel

### PHPUnit eller PHPStan klagar på cache

Rensa lokalt.

PowerShell från projektroten:

```powershell
Remove-Item -Recurse -Force .phpunit.cache, build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force .phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force .infection.cache* -ErrorAction SilentlyContinue
composer dump-autoload -o
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```

PowerShell från `github-settings/`:

```powershell
Remove-Item -Recurse -Force ..\.phpunit.cache, ..\build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force ..\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force ..\vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force ..\.infection.cache* -ErrorAction SilentlyContinue
composer -d .. dump-autoload -o
..\vendor\bin\phpunit -c ..\phpunit.xml --do-not-cache-result
```

Bash från projektroten:

```bash
rm -rf .phpunit.cache build/coverage
rm -f .phpunit.result.cache
rm -f vendor/bin/.phpunit.result.cache
rm -f .infection.cache*
composer dump-autoload -o
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```

---

## 7. Infection är långsam i början

Det är normalt.

Börja med:

```text
ENABLE_INFECTION_ON_PR=0
```

Kör Infection manuellt när du vill.

I det här projektet ska du normalt använda ett specifikt Infection-script:

```bash
composer infect:pcov
```

eller:

```bash
composer infect:xdebug
```

> Använd inte `composer infect` om du får meddelandet `Usage: php tools/infection.php pcov|xdebug`.

Från `github-settings/`:

```powershell
composer -d .. infect:pcov
```

eller:

```powershell
composer -d .. infect:xdebug
```

När testsviten är stabil:

```text
ENABLE_INFECTION_ON_PR=1
```

---

## 8. Rekommenderad mutation testing-policy

Ett bra upplägg:

```text
PR-gate:
  composer format:check
  composer stan
  composer test

Schedule/manuellt:
  composer infect:pcov
```

När projektet mognat:

```text
PR-gate:
  composer format:check
  composer stan
  composer test
  composer infect:pcov eller begränsad Infection
```

Om ni använder MSI-gränser:

```text
PR:       lite lägre krav för snabbare feedback
Schedule: striktare krav som kvalitetsbarometer
```

Exempel:

```text
PR:       --min-msi=90 --min-covered-msi=95
Schedule: --min-msi=100 --min-covered-msi=100
```

---

## 9. Håll PR:ar små

Bra uppdelning:

```text
PR 1: CI/infra
PR 2: appkod
PR 3: tester
PR 4: dokumentation
```

Det gör path-filter och felsökning enklare.

---

## 10. Förutse om Infection körs

På feature-branch:

### PowerShell

```powershell
git fetch origin
git diff --name-only origin/main...HEAD
```

### Bash

```bash
git fetch origin
git diff --name-only origin/main...HEAD
```

Om du ser:

```text
src/
tests/
composer.*
phpunit.xml
phpstan.neon
infection.json
```

kan Infection köras om:

```text
ENABLE_INFECTION_ON_PR=1
```

---

## 11. Frontend i CI

Om `ENABLE_FRONTEND_BUILD=1` bör workflowet köra:

```bash
npm ci
npm run start:build
```

Om projektet saknar frontend:

```text
ENABLE_FRONTEND_BUILD=0
```

Se mer:

- [`../docs/FRONTEND.md`](../docs/FRONTEND.md)

---

## 12. Required checks

Rekommenderade required checks i början:

```text
CI / php
```

Om frontend är separat jobb:

```text
CI / frontend
```

Vänta med att göra Infection required tills skip-logik och testsvit är stabila.

---

## 13. När du ändrar workflows

Om du ändrar:

```text
.github/workflows/**
tools/**
github-settings/**
```

Gör gärna:

1. kör workflow manuellt
2. kontrollera skip-logik
3. kontrollera artifacts
4. testa PR med liten ändring
5. verifiera required checks

---

## 14. Artifacts

Om workflowet skapar Infection-rapport kan artifact heta:

```text
infection-report
```

Kontrollera i GitHub Actions-run under artifacts.

Om artifact saknas kan orsaken vara:

- Infection kördes inte
- rapportfilen skapades inte
- upload-steget har villkor som inte matchade
- Infection failade tidigare

---

## 15. Vad körde GitHub egentligen?

När en Actions-körning skiljer sig från lokalt:

- kontrollera commit/SHA i run-detaljer
- kontrollera branch
- kontrollera workflow inputs
- kontrollera repo variables
- kontrollera om cache användes
- kontrollera PHP/Node-version
- kontrollera om branch är pushad

Lokalt:

```bash
git status
git log --oneline -5
```

---

## 16. När projektet mognat

När testsviten är stabil kan ni öka kvalitetssäkringen:

```text
ENABLE_INFECTION_ON_PR=1
```

Valfritt:

```text
ENABLE_INFECTION_ON_PUSH_MAIN=1
ENABLE_INFECTION_SCHEDULE=1
```

Rekommendation:

- slå på en sak i taget
- kontrollera CI-tid
- gör inte Infection required direkt om skip-logiken är ny
- justera MSI-gränser gradvis

---

## Relaterat

- [`CI_VARIABLES.md`](CI_VARIABLES.md)
- [`../docs/TESTING.md`](../docs/TESTING.md)
- [`../docs/FRONTEND.md`](../docs/FRONTEND.md)
- [`../docs/CLI.md`](../docs/CLI.md)
