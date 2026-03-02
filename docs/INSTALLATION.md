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
php radix scaffold:install <preset> [--force] [--dry-run]
```

**Options:**
- `<preset>` Namn eller path till preset under presets-root (t.ex. `auth`, `routes/auth`)
- `--force` Skriv över befintliga filer
- `--dry-run` Visa vad som skulle göras utan att skriva några filer

**Examples:**
```bash
php radix scaffold:install auth
php radix scaffold:install user
php radix scaffold:install admin --force
php radix scaffold:install routes/auth --dry-run
```

### 5.2 Viktigt: `--force` (p.g.a. "tomma" route-filer)

För att PHPStan inte ska klaga i en ny app kan starter-projektet innehålla vissa “tomma” filer (t.ex. route-filer).  
När du installerar ett scaffold behöver du därför ofta använda `--force` för att scaffoldet ska kunna skriva över dessa filer.

Exempel:

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
