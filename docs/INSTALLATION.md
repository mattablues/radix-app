# docs/INSTALLATION.md

← [`Tillbaka till index`](INDEX.md)

# Installation (Radix App)

Den här guiden gäller **Radix App** (starter-projektet som skapas via `composer create-project mattablues/radix-app`).

> Ramverket installeras som dependency i appen (du ska inte checka in framework-kod i app-repot).

---

## Krav

- PHP **8.3**
- Composer (används för att skapa/hantera projektet)
- Node.js + npm (för frontend-build om du använder assets)
- Databas (t.ex. MySQL eller SQLite) om du kör ORM/migrationer

---

## 1) Skapa projektet

```bash
composer create-project mattablues/radix-app <din-app>
cd <din-app>
```

---

## 2) Installera dependencies

I normalfallet är PHP-dependencies redan på plats efter `create-project`, men om du behöver:

```bash
composer install
```

Frontend (om du ska bygga assets):

```bash
npm install
```

---

## 3) Konfigurera miljö (.env)

Säkerställ att du har en `.env` på plats och att den innehåller nödvändiga värden för din miljö (t.ex. databasinställningar om du ska köra migrationer).

Default i starter är `SESSION_DRIVER=file` (du kan byta till `database` efter att du kört migrationer om du vill).

> Exakta nycklar varierar beroende på hur din `config/` är uppsatt. Se även `docs/CONFIG.md` när vi lägger tillbaka den.

---

## 4) Grundsetup via CLI (rekommenderat)

Starter-projektet innehåller en minimal databas-setup: **session-tabellen**.

Kör:

```bash
php radix app:setup
```

Det här kommandot:
- rensar cache
- kör migrationer (inkl. session-tabellen)
- kör seeders (om det finns några)

> Obs: `app:setup` kör alltså migrationerna åt dig i startläget.

---

## 5) Lägga till mer funktionalitet via scaffolds

Om du vill ha mer än “starter”-nivån installerar du ett scaffold (t.ex. `auth`, `user`, `admin`, `updates`).
Varje scaffold lägger till det som behövs för just det steget (inkl. migrationsfiler).

### 5.1 Scaffold-kommandot

```bash
php radix scaffold:install <preset>|--all [--force] [--force-placeholders] [--dry-run]
```

**Options:**
- `<preset>` Namn eller path till preset under presets-root (t.ex. `auth`, `routes/auth`)
- `--all` Installera ALLA presets under presets-root (top-level + dependencies)
- `--force` Skriv över befintliga filer (använd med försiktighet)
- `--force-placeholders` Skriv över endast placeholder-filer (rekommenderas i första hand)
- `--dry-run` Visa vad som skulle göras utan att skriva några filer

**Examples:**
```bash
php radix scaffold:install auth
php radix scaffold:install auth --dry-run
php radix scaffold:install auth --force-placeholders
php radix scaffold:install routes/auth --dry-run
php radix scaffold:install --all --dry-run
php radix scaffold:install --all --force-placeholders
```

### 5.2 Viktigt: när ska man använda `--force`?

I en ny app kan det finnas **placeholder-filer** (t.ex. tomma route-filer) för att verktyg som PHPStan ska vara nöjda direkt.
När du installerar ett scaffold kan dessa behöva ersättas.

- Använd i första hand:

```bash
php radix scaffold:install auth --force-placeholders
```

Det skriver bara över filer som är markerade som placeholders och minimerar risken att råka skriva över något du redan jobbat med.

- Använd `--force` endast när du **medvetet vill skriva över allt** som krockar (t.ex. om du vill “återställa” filer till scaffoldets version):

```bash
php radix scaffold:install auth --force
```

### 5.3 Kör migrationer efter scaffold-install

Eftersom scaffolds kan lägga till nya migrationsfiler behöver du efter installation köra:

```bash
php radix migrations:migrate
```

---

## 6) Nästa steg

- CLI-översikt: [`CLI.md`](CLI.md)
- Dokumentationsindex: [`INDEX.md`](INDEX.md)
