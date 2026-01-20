# Database: Migrations & Seeding

Radix använder ett versionshanterat system för databasscheman (migrations) och ett system för att fylla databasen med startdata (seeding).

## Migrations

Migreringar är PHP-filer som beskriver ändringar i databasen. De ligger i `database/migrations/`.

### Skapa en migrering
Använd CLI-verktyget för att generera en ny fil:
```bash
php radix make:migration create_users_table
```

### Migreringsstruktur
Varje migrering har en `up()`-metod (för att skapa/ändra) och en `down()`-metod (för att ångra).
```php
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

### Köra migreringar
```bash
php radix migrations:migrate
```

För att ångra den senaste migreringen:
```bash
php radix migrations:rollback
```

## Seeding

Seeders används för att fylla databasen med t.ex. en administratör eller testdata. De ligger i `database/seeds/`.

### Skapa en seeder
```bash
php radix make:seeder UserSeeder
```

### Köra seeds
```bash
php radix seeds:run
```

## Setup-kommandot

För att snabbt återställa hela databasen (rensa allt, kör alla migreringar och alla seeds) kan du använda:
```bash
php radix app:setup --fresh
```
*Varning: Detta raderar all befintlig data i databasen!*