# Radix ORM
<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Innehåll](#inneh%C3%A5ll)
- [Installation](#installation)
- [Modellbas](#modellbas)
- [Statisk API på modeller](#statisk-api-p%C3%A5-modeller)
- [Instans-API](#instans-api)
- [Query Builder – metodsignaturer](#query-builder--metodsignaturer)
- [Relationer – signaturer](#relationer--signaturer)
  - [Many-to-many (om stöd finns för pivot-helpers)](#many-to-many-om-st%C3%B6d-finns-f%C3%B6r-pivot-helpers)
- [Scopes](#scopes)
- [Paginering](#paginering)
- [Massassignering](#massassignering)
- [Soft deletes](#soft-deletes)
- [Transaktioner](#transaktioner)
- [Vanliga recept](#vanliga-recept)
- [Felhantering](#felhantering)
- [Bästa praxis](#b%C3%A4sta-praxis)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Active Record–inspirerad ORM under namespace `Radix\Database\ORM`:
- Modeller som ärver `Radix\Database\ORM\Model`
- Kedjebar query-API
- Relationer (hasOne, hasMany, belongsTo, belongsToMany)
- Eager/lazy loading
- Paginering
- Soft deletes och restore
- Massassignering via `fill()`

## Innehåll
- [Installation](#installation)
- [Modellbas](#modellbas)
- [Statisk API på modeller](#statisk-api-på-modeller)
- [Instans-API](#instans-api)
- [Query Builder – metodsignaturer](#query-builder--metodsignaturer)
- [Relationer – signaturer](#relationer--signaturer)
  - [Many-to-many (om stöd finns för pivot-helpers)](#many-to-many-om-stöd-finns-för-pivot-helpers)
- [Scopes](#scopes)
- [Paginering](#paginering)
- [Massassignering](#massassignering)
- [Soft deletes](#soft-deletes)
- [Transaktioner](#transaktioner)
- [Vanliga recept](#vanliga-recept)
- [Felhantering](#felhantering)
- [Bästa praxis](#bästa-praxis)

## Installation
- PHP 8.3+
- Konfigurera databas i `.env` och config
- Autoload via Composer

## Modellbas
- Ärvt från `Radix\Database\ORM\Model`
- Relationer under `Radix\Database\ORM\Relationships`

php
hasOne(Status::class, 'user_id', 'id'); } }

## Statisk API på modeller
- `find(string|int $id, bool $withTrashed = false): ?static`
- `with(string|array $relations): static|Builder`
- `where(string $column, mixed $operatorOrValue, mixed $value = null): Builder`
- `orderBy(string $column, string $direction = 'asc'): Builder`
- `first(): ?static`
- `get(): array<static>`
- `paginate(int $perPage, int $page): Paginator|array`
- `getOnlySoftDeleted(): Builder`

Exempel:

php
paginate(10, 1); // Filtrering $latest = User::where('email', 'like', '%@example.com%') ->orderBy('id', 'desc') ->paginate(15, 1); // Endast soft-deleted $closed = User::getOnlySoftDeleted()->paginate(10, 1);

## Instans-API
- `fill(array $attributes): static`
- `save(): bool`
- `delete(): bool`  (soft delete om modellen stöder det)
- `restore(): bool`
- `loadMissing(string|array $relations): void`
- `getRelation(string $name): mixed`

Exempel:

php
fill([ 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', ]); $user->password = 'hemligt'; // explicit utanför fill() $user->save(); // Lazy load om saknas $user->loadMissing('status'); $status = $user->getRelation('status'); $status->fill(['status' => 'activate'])->save(); // Soft delete + restore $user->delete(); $u = User::find($user->id, true); $u?->restore();

## Query Builder – metodsignaturer
- `select(array|string $columns): Builder`
- `where(string $column, mixed $operatorOrValue, mixed $value = null): Builder`
- `orWhere(string $column, mixed $operatorOrValue, mixed $value = null): Builder`
- `whereIn(string $column, array $values): Builder`
- `whereNull(string $column): Builder`
- `whereNotNull(string $column): Builder`
- `whereColumn(string $left, string $operator, string $right): Builder`
- `orderBy(string $column, string $direction = 'asc'): Builder`
- `limit(int $limit): Builder`
- `offset(int $offset): Builder`
- `first(): ?Model`
- `get(): array<Model>`
- `count(): int`
- `max(string $column): mixed`
- `exists(): bool`
- `insert(array $rowsOrRow): bool`
- `update(array $attributes): int`
- `delete(): int`
- `paginate(int $perPage, int $page): Paginator|array`

Exempel:

php
whereIn('role', ['editor', 'author']) ->whereNull('deleted_at') ->orderBy('id', 'desc') ->select(['id','first_name','email']) ->limit(10) ->get(); $total = User::where('status', 'draft')->count(); $maxId = User::max('id'); $exists = User::where('email', 'john@example.com')->exists();

## Relationer – signaturer
- `hasOne(Related::class, string $foreignKey, string $localKey = 'id')`
- `hasMany(Related::class, string $foreignKey, string $localKey = 'id')`
- `belongsTo(Related::class, string $foreignKey, string $ownerKey = 'id')`
- `belongsToMany(Related::class, string $pivotTable, string $foreignKey, string $relatedKey)`

Exempel:

php
belongsTo(User::class, 'user_id', 'id'); } } // Eager $user = User::with('status')->find(123); // Lazy on demand $user->loadMissing('status'); $status = $user->getRelation('status'); $status->fill(['active' => 'offline'])->save();

### Many-to-many (om stöd finns för pivot-helpers)
- `attach(int|array $ids): void`
- `detach(int|array $ids = null): void`
- `sync(array $ids): void`

php
belongsToMany(Role::class, 'role_user', 'user_id', 'role_id'); } } $user = User::find(1); $user->roles()->sync([2,3]); $user->roles()->attach(4); $user->roles()->detach(2);

## Scopes
Konvention: `scopeXxx(Builder $q, ...$args)` → anropas som `Model::xxx(...$args)`.

php
where('status', 'published'); } } $latest = Post::published() ->orderBy('published_at','desc') ->limit(5) ->get();

## Paginering

php
paginate(10, $page); // Paginator returnerar items + metadata (exakt struktur beror på implementation)


## Massassignering
- Definiera `protected array $fillable` på modellen
- `fill(array $attributes)` följer `$fillable`
- Övriga fält sätts explicit som egenskaper

php
fill(['first_name' => 'Eve', 'email' => 'eve@ex.com']); $user->password = 'nytt'; $user->save();

## Soft deletes
- `getOnlySoftDeleted(): Builder`
- `find($id, true)`: inkludera soft-deleted
- `restore(): bool`
- `delete(): bool`

php
paginate(10, 1); $user = User::find(5, true); $user?->restore();

## Transaktioner
Använd din databasmanager/connection-klass enligt projektets konfiguration.

## Vanliga recept
Update-or-create (om stöd finns):

php
first() ?->fill(['name' => 'Alice'])->save(); // Alternativt: egen helper updateOrCreate($filters, $values) om implementerat

Batch insert:

php
'A', 'email' => 'a@ex.com'], ['first_name' => 'B', 'email' => 'b@ex.com'], ]);

Exists/subquery:

php
from('posts')->whereColumn('posts.user_id', 'users.id'); })->get();

## Felhantering
- `find()` och `first()` kan returnera `null`
- Kontrollera relationer eller använd `loadMissing()`
- Hantera `save()/delete()`-returvärden där det är kritiskt
- Använd transaktioner för konsistens

## Bästa praxis
- Ange `$table` och `$primaryKey` om de avviker
- Lista tillåtna fält i `$fillable`
- Använd `with()` för att undvika N+1
- Håll validering/service-logik utanför modellen