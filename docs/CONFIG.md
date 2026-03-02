# docs/CONFIG.md

← [`Tillbaka till index`](INDEX.md)

# Konfiguration (Radix App)

Den här guiden beskriver hur konfigurationen i **Radix App** är upplagd och vad du typiskt behöver justera när du kör projektet lokalt eller deployar.

---

## Översikt

Radix App använder två huvudkällor för konfiguration:

1) **`.env`**  
   Miljövariabler för den aktuella miljön (lokalt, test, production).  
   `.env` ska normalt **inte** commit:as.

2) **`config/*.php`**  
   PHP-filer som returnerar arrayer med appens konfiguration.

Vid runtime laddas `.env` tidigt och konfigurationsfilerna under `config/` läses in och slås ihop till en samlad konfiguration som sedan injiceras i containern.

---

## Var konfigen finns

- `.env` (miljöspecifik)
- `config/` (konfig-filer)
- `storage/` (persistenta filer, t.ex. uppladdningar/loggar beroende på setup)
- `cache/` (cacheade filer, t.ex. view/cache/config/cache beroende på setup)

---

## Viktigt: `.env` är ett krav

Appen förväntar sig att en `.env` finns och är giltig för din miljö.

Om något saknas eller är fel brukar det märkas tidigt vid boot (t.ex. genom env-validering) så att du inte får “mystiska fel” senare i request-kedjan.

---

## Databas

Om du använder migrationer/ORM behöver din `.env` matcha databas-setupen.

Typiskt behöver du ha koll på:

- databas-driver (t.ex. `mysql` eller `sqlite`)
- host/port (för mysql)
- databasnamn/fil (för sqlite)
- användare/lösenord (för mysql)

Efter att du har justerat databasen kör du:

```bash
php radix migrations:migrate
```

---

## Sessions (file vs database)

Projektet stödjer sessions via både `file` och `database`.

### Rekommenderat flöde vid database sessions

1) Börja med file-sessions under installation/initial setup (för att undvika problem innan tabeller finns).

2) Kör setup/migrationer så att session-tabellen skapas.

3) Byt sedan till database-sessions.

Om du kör `app:setup` och har `SESSION_DRIVER=database` innan tabellen finns, kan det orsaka problem. Då är det bättre att temporärt köra med file-sessions tills tabellen är på plats.

---

## Cache och felsökning

Om du ändrar konfiguration, templates eller scaffoldar in nya filer och något beter sig konstigt:

```bash
php radix cache:clear
```

---

## Tips: håll config och env tydliga

- Lägg **hemligheter** och miljöspecifika saker i `.env`
- Lägg **standardbeteenden** och appens “default settings” i `config/`
- Håll `.env.example` uppdaterad (om ni använder den i projektet), men commit:a inte `.env`

---

## Nästa steg

- CLI: [`CLI.md`](CLI.md)
- Installation: [`INSTALLATION.md`](INSTALLATION.md)
- Routing: [`ROUTING.md`](ROUTING.md)
