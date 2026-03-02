# docs/DATABASE.md

← [`Tillbaka till index`](INDEX.md)

# Database: migrations & seeders (Radix App)

Radix använder ett versionshanterat system för databasscheman (**migrations**) och ett system för att fylla databasen med startdata (**seeders**).

---

## Migrations

Migreringar är PHP-filer som beskriver ändringar i databasen. De ligger i:

- `database/migrations/`

### Skapa en migration

`make:migration` stödjer en *operation* (t.ex. `create`, `alter`) + tabellnamn.

```bash
php radix make:migration <operation> <table>
```

Exempel:

```bash
php radix make:migration create users
php radix make:migration alter users
php radix make:migration create blog_posts
```

> Operationen måste ha en matchande stub under templates (t.ex. `create_table.stub`, `alter_table.stub`).

### Struktur (up/down)

Varje migration har:

- `up()` (applicera ändringen)
- `down()` (ångra ändringen)

```php
<?php

use Radix\Database\Migration\Schema;
use Radix\Database\Migration\Table;

return new class {
    public function up(Schema $schema): void {
        $schema->create('users', function (Table $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void {
        $schema->drop('users');
    }
};
```

### Köra migrations

```bash
php radix migrations:migrate
```

### Rollback (senaste migrationer)

```bash
php radix migrations:rollback
```

> Tips: Rollback är bra i development, men ska användas med extra eftertanke i production.

---

## Seeders

Seeders används för att fylla databasen med t.ex. en admin-användare eller testdata.

Seeders ligger typiskt i:

- `database/seeders/`

### Skapa en seeder

```bash
php radix make:seeder UserSeeder
```

### Köra seeders

```bash
php radix seeds:run
```

### Rollback seeders (om stöds i din setup)

```bash
php radix seeds:rollback
```

---

## `app:setup` (snabb setup)

För att snabbt få en fungerande databas lokalt kan du köra:

```bash
php radix app:setup
```

Det kommandot:
- rensar cache
- kör migrations
- kör seeders (om det finns några)

### `--fresh` (wipa databasen)

Om du vill återställa databasen helt och köra om allt:

```bash
php radix app:setup --fresh
```

**Varning:** `--fresh` raderar befintlig data.

---

## Scaffolds och migrationer

Scaffolds kan lägga till nya migrationsfiler.

Efter att du installerat ett scaffold (ofta med `--force`) behöver du därför vanligtvis köra:

```bash
php radix migrations:migrate
```
