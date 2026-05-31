# docs/DATABASE.md

← [`Tillbaka till index`](INDEX.md)

# Database: migrations & seeders (Radix App)

Radix App använder ett versionshanterat system för databasscheman via **migrations** och ett system för startdata via **seeders**.

Databasfunktionaliteten kommer huvudsakligen från **Radix Framework**, medan appens egna migrations och seeders ligger i projektet.

---

## Översikt

Vanliga delar:

```text
database/
  migrations/
  seeders/
```

CLI-kommandon:

```bash
php radix migrations:migrate
php radix migrations:rollback
php radix seeds:run
php radix seeds:rollback
php radix make:migration create users
php radix make:seeder UserSeeder
php radix app:setup
```

---

## Konfiguration

Databasinställningar ligger normalt i:

```text
.env
config/database.php
```

Vanliga `.env`-nycklar:

```text
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=radix
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## MySQL

Exempel:

```text
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=radix
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Skapa databasen först om den inte finns.

Kör sedan:

```bash
php radix migrations:migrate
```

---

## SQLite

Om appens databasconfig stödjer SQLite kan du använda:

```text
DB_DRIVER=sqlite
```

Hur filvägen anges beror på `config/database.php`.

Efter ändring:

```bash
php radix migrations:migrate
```

---

## Migrations

Migrations är PHP-filer som beskriver ändringar i databasschemat.

De ligger i:

```text
database/migrations/
```

Exempel på migrations:

```text
20250730022321_create_sessions.php
20250730091522_create_users.php
20250730222004_create_status.php
20250814201307_create_tokens.php
20260117215631_create_system_events.php
20260117215717_create_system_updates.php
20260518104523_create_blocked_emails.php
```

Vilka migrations som finns beror på installerade scaffolds.

---

## Skapa en migration

Använd:

```bash
php radix make:migration <operation> <table_name>
```

Exempel:

```bash
php radix make:migration create users
php radix make:migration alter users
php radix make:migration create blog_posts
```

`operation` måste ha en matchande stub i templates.

Exempel:

```text
create_table.stub
alter_table.stub
```

Kör hjälp:

```bash
php radix make:migration --help
```

---

## Migration-filnamn

Migrationer får normalt ett timestamp-prefix.

Exempel:

```text
20260518104523_create_blocked_emails.php
```

Det gör att migrationerna kan köras i rätt ordning.

---

## Migration-struktur

En migration har normalt:

```text
up()
down()
```

`up()` applicerar ändringen.

`down()` ångrar ändringen.

Exempel:

```php
<?php

declare(strict_types=1);

use Radix\Database\Migration\Schema;
use Radix\Database\Migration\Table;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('users', function (Table $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->drop('users');
    }
};
```

---

## Köra migrations

Kör alla migrations som ännu inte är körda:

```bash
php radix migrations:migrate
```

Det används normalt efter:

- första installation
- scaffold-installation
- ny migration
- uppdatering där nya migrations tillkommit

---

## Rollback migrations

Rollback:

```bash
php radix migrations:rollback
```

Rollback ska användas med eftertanke.

I utvecklingsmiljö är rollback användbart för att testa migrationer.

I production kan rollback innebära risk för dataförlust beroende på hur `down()` är skriven.

---

## `app:setup`

För snabb setup kan du köra:

```bash
php radix app:setup
```

Det kommandot gör normalt:

- rensar cache
- kör migrations
- kör seeders om det finns några

Det är rekommenderat första setup-kommandot efter installation.

---

## `app:setup --fresh`

För att återställa databasen och köra om setup:

```bash
php radix app:setup --fresh
```

Varning:

```text
--fresh kan radera befintlig data.
```

Använd bara i utvecklingsmiljö eller när du verkligen vill återställa databasen.

Undvik i production om du inte vet exakt vad du gör.

---

## Seeders

Seeders används för att fylla databasen med startdata eller testdata.

De ligger i:

```text
database/seeders/
```

Exempel:

```text
00000000000000_database_seeder.php
20250101010101_users_seeder.php
20251128001505_status_seeder.php
20260119190718_system_updates_seeder.php
admin.seeders.php
auth.seeders.php
```

Vilka seeders som finns beror på app och installerade scaffolds.

---

## Skapa en seeder

Använd:

```bash
php radix make:seeder UserSeeder
```

Kör hjälp:

```bash
php radix make:seeder --help
```

---

## Köra seeders

Kör seeders:

```bash
php radix seeds:run
```

Seeders kan användas för till exempel:

- admin-användare
- statusvärden
- systemuppdateringar
- testdata
- standardinställningar

---

## Rollback seeders

Om appens seeders stödjer rollback:

```bash
php radix seeds:rollback
```

Stöd och beteende beror på hur seeders är skrivna.

---

## Database seeder

Appen kan ha en samlande seeder, till exempel:

```text
database/seeders/00000000000000_database_seeder.php
```

Den kan användas för att köra andra seeders i en kontrollerad ordning.

---

## Scaffolds och databasen

Scaffolds kan lägga till:

- migrations
- seeders
- modeller
- config
- routes/controllers som använder nya tabeller

Efter scaffold-installation bör du normalt köra:

```bash
php radix migrations:migrate
```

Om scaffoldet lägger till seeders kan du även behöva köra:

```bash
php radix seeds:run
```

Exempel:

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
php radix seeds:run
```

För alla top-level scaffolds:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
php radix seeds:run
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Sessions och databas

Radix App kan använda sessions i databas.

I `.env`:

```text
SESSION_DRIVER=database
SESSION_TABLE=sessions
```

Men vid första installation rekommenderas ofta:

```text
SESSION_DRIVER=file
```

Flöde:

1. använd `SESSION_DRIVER=file`
2. kör migrationer
3. byt till `SESSION_DRIVER=database`
4. kör appen igen

Det undviker problem innan session-tabellen finns.

Se mer i:

- [`CONFIG.md`](CONFIG.md)

---

## ORM och modeller

Modeller ligger normalt i:

```text
src/Models/
```

ORM-konfiguration kan ligga i:

```text
config/orm.php
```

och `.env` kan innehålla:

```text
ORM_MODEL_NAMESPACE=
```

Se mer i:

- [`ORM.md`](ORM.md)

---

## Query builder

Radix Framework innehåller query builder/databaslager som används av ORM och annan databaslogik.

Exakt API beskrivs mer i:

- [`ORM.md`](ORM.md)

---

## Production safety

Databaskommandon kan vara känsliga i production.

Särskilt:

```bash
php radix migrations:migrate
php radix migrations:rollback
php radix app:setup --fresh
```

Om appen använder deploy-skydd kan `.env` innehålla:

```text
RADIX_DEPLOY=0
```

Sätt bara deploy-flagga för en medveten körning om din setup kräver det.

Rekommendation:

- kör backup innan migrations i production
- testa migrationer lokalt/staging först
- granska `down()` innan rollback
- använd inte `--fresh` i production
- logga deploy-körningar

---

## Migration best practices

### Gör migrationer små

Bra:

```text
create_users
add_email_verified_at_to_users
create_tokens
```

Sämre:

```text
change_everything
```

### Skriv alltid `down()`

Även om rollback sällan används bör migrationen beskriva hur ändringen kan ångras.

### Var försiktig med dataförlust

Exempel på riskabla operationer:

```text
drop table
drop column
truncate
rename column utan backup
ändra datatyp med inkompatibel data
```

### Separera schema och data

Använd migrations för schema.

Använd seeders för startdata.

### Testa migrationer

I development:

```bash
php radix migrations:migrate
php radix migrations:rollback
php radix migrations:migrate
```

---

## Seeder best practices

### Gör seeders idempotenta när möjligt

En seeder bör helst kunna köras flera gånger utan att skapa dubbletter.

Exempelprincip:

```text
hitta befintlig rad
om den finns: uppdatera
om den saknas: skapa
```

### Håll production-data säker

Seeders som skapar admin-konton eller tokens bör inte hårdkoda känsliga lösenord i kod.

Använd `.env` eller ett säkert manuellt flöde.

### Dela upp seeders

Bra:

```text
UsersSeeder
StatusSeeder
SystemUpdatesSeeder
```

Sämre:

```text
EverythingSeeder
```

---

## Vanliga flöden

### Första installation

```bash
php radix app:setup
```

### Efter scaffold

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

### Skapa ny tabell

```bash
php radix make:migration create posts
php radix migrations:migrate
```

### Ändra befintlig tabell

```bash
php radix make:migration alter users
php radix migrations:migrate
```

### Skapa och köra seeder

```bash
php radix make:seeder StatusSeeder
php radix seeds:run
```

### Återställ lokalt

```bash
php radix app:setup --fresh
```

---

## Felsökning

### Databasanslutning misslyckas

Kontrollera `.env`:

```text
DB_DRIVER
DB_HOST
DB_PORT
DB_NAME
DB_USERNAME
DB_PASSWORD
DB_CHARSET
```

Kontrollera att databasen finns och att användaren har rättigheter.

### Migration hittar inte stub

Om du kör:

```bash
php radix make:migration create posts
```

behöver det finnas en stub för operationen, till exempel:

```text
create_table.stub
```

Om du kör:

```bash
php radix make:migration alter users
```

behöver det finnas:

```text
alter_table.stub
```

### Migration körs inte

Kontrollera:

- att filen ligger i `database/migrations/`
- att filnamnet har timestamp
- att migrationen inte redan är körd
- att `up()` finns
- att databaskopplingen fungerar

### Rollback fungerar inte

Kontrollera:

- att `down()` finns
- att `down()` matchar det som gjordes i `up()`
- att databasobjektet finns
- att operationen inte skulle förstöra data oväntat

### Session-tabellen saknas

Om du använder database sessions:

```text
SESSION_DRIVER=database
```

men tabellen inte finns, byt tillfälligt till:

```text
SESSION_DRIVER=file
```

Kör sedan:

```bash
php radix migrations:migrate
```

Byt tillbaka när tabellen finns.

### Seeders skapar dubbletter

Gör seeders idempotenta genom att kontrollera om data redan finns innan den skapas.

---

## Relaterat

- [`CONFIG.md`](CONFIG.md)
- [`CLI.md`](CLI.md)
- [`ORM.md`](ORM.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)
- [`TESTING.md`](TESTING.md)
