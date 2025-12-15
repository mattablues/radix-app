# CI Template Setup (checklista när du skapar ett nytt repo från Radix-template)

Det här dokumentet är en “gör-så-här”-lista för att få CI stabilt och förutsägbart i ett nytt repo som bygger på templaten.

## 0) Förutsättningar
- Repo har `CI` workflow under `.github/workflows/ci.yml`.
- Projektet använder Composer (PHP 8.3) och ev. Node (frontend build).
- Repo-variabler används för att styra delar av CI (se `github-settings/CI_VARIABLES.md`).

---

## 1) Direkt efter att du skapat repot (GitHub UI)

### 1.1 Sätt Actions-variabler (repo variables)
GitHub → **Settings** → **Secrets and variables** → **Actions** → **Variables**

Rekommenderad start för projekt med frontend (“Preset A”):

```text
ENABLE_FRONTEND_BUILD=1 
ENABLE_INFECTION_ON_PR=0 
ENABLE_INFECTION_ON_CI_CHANGES=0 
ENABLE_INFECTION_ON_PUSH_MAIN=0
```

När projektet är stabilt kan du senare ändra:

```text
ENABLE_INFECTION_ON_PR=1
```

### 1.2 Kontrollera Actions-permissions
GitHub → **Settings** → **Actions** → **General**
- Se till att GitHub Actions är tillåtet.
- Om repo ligger i en organisation: kontrollera att org-policy inte blockerar Actions.

### 1.3 (Valfritt men rekommenderat) Branch protection / required checks
GitHub → **Settings** → **Branches** → “Add branch protection rule” för `main`:

Rekommendation:
- Require a pull request before merging: **ON**
- Require status checks to pass before merging: **ON**
  - välj era CI-jobb som ska vara required (t.ex. “CI / php” och “CI / infection”)
- Require branches to be up to date before merging: **ON** (om ni vill ha strikt)
- Allow force pushes: **OFF**
- Allow deletions: **OFF**

Tips: Om du vill att PR alltid ska kunna mergeas även när Infection skippas, gör Infection-jobbet “required” men se till att det alltid avslutas med success (er setup skippar med exit 0).

---

## 2) Första körningen: verifiera att CI funkar i repot

### 2.1 Triggera workflow manuellt
GitHub → **Actions** → Workflow “CI” → **Run workflow**

Låt default vara:
- `run_infection_mode = schedule`

### 2.2 Kontrollera artifacts
I körningen: öppna steget “Upload Infection report”.
- Du ska se artifact “infection-report” om filen skapades.

---

## 3) Lokal setup: minimera “det funkar på min dator”-problem

### 3.1 Kör samma checks lokalt (PowerShell)

```powershell
composer install 
composer format:check 
composer stan 
vendor/bin/phpunit -c phpunit.xml --display-deprecations --display-errors --display-notices --do-not-cache-result
```

### 3.2 Om frontend finns: kör build lokalt

```powershell
npm ci npm run start:build
```
---
## 4) Vanliga första-fel och snabbfixar

### 4.1 PHPUnit/Stan klagar på cache/artefakter
Rensa och kör om:

```powershell
Remove-Item -Recurse -Force .phpunit.cache, build\coverage -ErrorAction SilentlyContinue
Remove-Item -Force .phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force vendor\bin\.phpunit.result.cache -ErrorAction SilentlyContinue
Remove-Item -Force .infection.cache* -ErrorAction SilentlyContinue
composer dump-autoload -o
vendor/bin/phpunit -c phpunit.xml --do-not-cache-result
```
### 4.2 Infection är långsam / för tung i början
Det är normalt i nya projekt.

**A) Stäng av Infection på PR i början**

```text
ENABLE_INFECTION_ON_PR=0
```

Behåll schedule-jobbet som säkerhetsnät.

**B) Kör Infection bara nattligt/manuellt**
Behåll PR avstängt, men använd “Run workflow” när du vill verifiera mutationer.

### 4.3 Infection hittar “1 mutant escaped” i tidskänslig kod
Gör testet deterministiskt:
- undvik asserts som kräver exakta `sleep()`-timingar i CI
- använd hellre:
  - stabil input
  - reflection för defaultvärden
  - loop med deadline i stället för exakt sleep när du måste vänta på TTL

---

## 5) Rekommenderat arbetssätt i vardagen

### 5.1 Håll PR:ar små
- En PR för “CI/infra”
- En PR för “kod”
- En PR för “tester”

Det gör att path-filter fungerar som tänkt och att felsökning blir enklare.

### 5.2 Förutse om Infection kommer köras
På din feature-branch:

```powershell
git fetch origin git diff --name-only origin/main...HEAD
```

Om du ser `src/`, `framework/src/` eller `tests/` → räkna med Infection (om `ENABLE_INFECTION_ON_PR=1`).

---

## 6) När projektet mognat (uppgradera CI)
När ni har stabil testsvit och vill höja kvaliteten:

1) Sätt:
```text
ENABLE_INFECTION_ON_PR=1
```

2) (Valfritt) Överväg om Infection ska köras på push till main:

```text
ENABLE_INFECTION_ON_PUSH_MAIN=1
```


Detta kan bli tungt — använd bara om ni verkligen vill.

---

## 7) Snabb felsökning: “vad körde GitHub egentligen?”
När en Actions-körning ser annorlunda ut än lokalt:
- kolla vilken commit/SHA workflowen körde på (visas i run-detaljer)
- jämför med din lokala branch/commit
- säkerställ att din branch är pushad och att du tittar på rätt run