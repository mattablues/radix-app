# docs/ORM.md

← [`Tillbaka till index`](INDEX.md)

# ORM (Radix App)

Radix App använder ORM- och QueryBuilder-funktionalitet från **Radix Framework**.

ORM-lagret ger:

- modeller
- query builder
- hydrering till model-objekt
- relationer
- pagination
- search
- mutationer
- soft deletes
- eager loading
- aggregat
- transaktioner
- debug helpers

Modeller i appen ligger normalt under:

```text
src/Models/
```

---

## Översikt

En modell representerar normalt en databastabell.

Exempel:

```text
App\Models\User      -> users
App\Models\Token     -> tokens
App\Models\Status    -> status
```

Modeller ärver från:

```php
Radix\Database\ORM\Model
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

final class User extends Model
{
    protected string $table = 'users';

    protected string $primaryKey = 'id';

    protected bool $timestamps = true;

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

---

## Installation och setup

ORM kräver:

- PHP 8.3
- PDO
- fungerande databasconfig
- körda migrations

Databasinställningar finns normalt i:

```text
.env
config/database.php
config/orm.php
```

Kör migrationer:

```bash
php radix migrations:migrate
```

Se mer i:

- [`DATABASE.md`](DATABASE.md)
- [`CONFIG.md`](CONFIG.md)

---

## Modellnamespace

ORM-konfiguration kan använda env-värde:

```text
ORM_MODEL_NAMESPACE=
```

Exempel:

```text
ORM_MODEL_NAMESPACE="App\\Models\\"
```

Hur detta används beror på appens `config/orm.php` och bootstrap.

---

## Snabbstart

```php
<?php

declare(strict_types=1);

use App\Models\User;

$users = User::select(['id', 'name', 'email'])
    ->where('status', '=', 'active')
    ->orderBy('name')
    ->limit(10)
    ->get();

foreach ($users as $user) {
    echo $user->name;
}
```

Hämta en rad:

```php
$user = User::find(1);
```

Hämta första matchningen:

```php
$user = User::where('email', '=', 'admin@example.com')->first();
```

Hämta ett enskilt värde:

```php
$email = User::where('id', '=', 1)->value('email');
```

Hämta kolumnlista:

```php
$emails = User::where('status', '=', 'active')->pluck('email');
```

---

## `User::query()` behövs inte alltid

Radix Model skickar statiska anrop vidare till QueryBuilder.

Det betyder att du kan skriva:

```php
User::where('status', '=', 'active')->get();
```

i stället för:

```php
User::query()->where('status', '=', 'active')->get();
```

Båda mönstren kan användas.

---

## Skapa en modell

Via CLI:

```bash
php radix make:model User
```

Kör hjälp:

```bash
php radix make:model --help
```

Modeller skapas normalt under:

```text
src/Models/
```

---

## Modellens grundegenskaper

Vanliga properties:

```php
protected string $table = 'users';

protected string $primaryKey = 'id';

protected bool $timestamps = true;

protected bool $softDeletes = false;

protected array $fillable = [
    'name',
    'email',
];

protected array $guarded = [];
```

### `$table`

Anger tabellnamn:

```php
protected string $table = 'users';
```

### `$primaryKey`

Anger primärnyckel:

```php
protected string $primaryKey = 'id';
```

### `$timestamps`

Om modellen ska sätta `created_at` och `updated_at` vid `save()`:

```php
protected bool $timestamps = true;
```

### `$softDeletes`

Om modellen använder soft deletes:

```php
protected bool $softDeletes = true;
```

Då används kolumnen:

```text
deleted_at
```

### `$fillable`

Lista över fält som får massfyllas:

```php
protected array $fillable = [
    'name',
    'email',
];
```

### `$guarded`

Lista över fält som inte ska fyllas automatiskt:

```php
protected array $guarded = [
    'password',
];
```

---

## Hämta data

### `get()`

Returnerar en Collection med modeller:

```php
$users = User::where('status', '=', 'active')->get();
```

### `first()`

Returnerar första modellen eller `null`:

```php
$user = User::where('email', '=', $email)->first();
```

### `firstOrFail()`

Returnerar första modellen eller kastar exception:

```php
$user = User::where('email', '=', $email)->firstOrFail();
```

### `find()`

Hämtar via primärnyckel:

```php
$user = User::find(1);
```

### `all()`

Hämtar alla rader:

```php
$users = User::all();
```

---

## Select

Välj kolumner:

```php
$users = User::select(['id', 'name', 'email'])->get();
```

Med alias:

```php
$users = User::select([
    'id',
    'users.name AS user_name',
])->get();
```

Raw select:

```php
$rows = User::selectRaw('COUNT(id) AS total')->get();
```

Subquery i select:

```php
$sub = User::selectRaw('COUNT(*)')
    ->from('orders')
    ->whereColumn('orders.user_id', '=', 'users.id');

$users = User::from('users')
    ->select(['users.*'])
    ->selectSub($sub, 'orders_count')
    ->get();
```

---

## From

Ange tabell:

```php
$query = User::from('users');
```

Med alias:

```php
$query = User::from('users AS u');
```

Raw from:

```php
$query = User::fromRaw('(SELECT * FROM users WHERE active = 1) AS u');
```

---

## Where

Grundläggande where:

```php
$users = User::where('status', '=', 'active')->get();
```

Flera villkor:

```php
$users = User::where('status', '=', 'active')
    ->where('role', '=', 'admin')
    ->get();
```

Or where:

```php
$users = User::where('role', '=', 'admin')
    ->orWhere('role', '=', 'moderator')
    ->get();
```

---

## Where-varianter

```php
User::whereIn('id', [1, 2, 3])->get();

User::whereNotIn('role', ['admin', 'editor'])->get();

User::whereBetween('age', [18, 30])->get();

User::whereNotBetween('score', [50, 80])->get();

User::whereColumn('users.country_id', '=', 'countries.id')->get();

User::whereNull('deleted_at')->get();

User::whereNotNull('email_verified_at')->get();

User::whereLike('email', '%@example.com')->get();
```

Raw where med bindningar:

```php
$users = User::whereRaw(
    '(`first_name` LIKE ? OR `last_name` LIKE ?)',
    ['%ma%', '%ma%']
)->get();
```

---

## Subqueries

### WHERE EXISTS

```php
$sub = User::select(['id'])
    ->from('orders')
    ->whereColumn('orders.user_id', '=', 'users.id');

$users = User::from('users')
    ->whereExists($sub)
    ->get();
```

### WHERE IN med subquery

```php
$sub = User::select(['user_id'])
    ->from('orders')
    ->where('status', '=', 'paid');

$users = User::from('users')
    ->where('id', 'IN', $sub)
    ->get();
```

---

## Joins

```php
$users = User::from('users')
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->get();
```

Left join:

```php
$users = User::from('users')
    ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
    ->get();
```

Right join:

```php
$users = User::from('users')
    ->rightJoin('roles', 'users.role_id', '=', 'roles.id')
    ->get();
```

Full join:

```php
$users = User::from('users')
    ->fullJoin('addresses', 'users.id', '=', 'addresses.user_id')
    ->get();
```

Raw join:

```php
$users = User::from('users')
    ->joinRaw(
        'INNER JOIN `teams` ON `teams`.`id` = `users`.`team_id` AND `teams`.`active` = ?',
        [1]
    )
    ->get();
```

Join subquery:

```php
$sub = User::select(['id', 'user_id'])
    ->from('orders')
    ->where('status', '=', 'completed');

$users = User::from('users')
    ->joinSub($sub, 'completed_orders', 'users.id', '=', 'completed_orders.user_id')
    ->get();
```

---

## Group, having och order

Group by:

```php
$rows = User::from('users')
    ->selectRaw('role, COUNT(*) AS total')
    ->groupBy('role')
    ->get();
```

Having:

```php
$rows = User::from('users')
    ->selectRaw('role, COUNT(*) AS total')
    ->groupBy('role')
    ->having('total', '>', 10)
    ->get();
```

Having raw:

```php
$rows = User::from('users')
    ->groupBy('role')
    ->havingRaw('COUNT(*) > ?', [5])
    ->get();
```

Order by:

```php
$users = User::orderBy('name', 'ASC')->get();
```

Descending:

```php
$users = User::orderByDesc('created_at')->get();
```

Latest/oldest:

```php
$latest = User::latest()->get();

$oldest = User::oldest()->get();
```

Raw order:

```php
$users = User::orderByRaw('FIELD(role, "admin", "editor", "user")')->get();
```

---

## Limit och offset

```php
$users = User::orderBy('name')
    ->limit(10)
    ->offset(20)
    ->get();
```

---

## Union

```php
$q1 = User::select(['id', 'name'])
    ->from('users')
    ->where('status', '=', 'active');

$q2 = User::select(['id', 'name'])
    ->from('archived_users')
    ->where('status', '=', 'active');

$rows = $q1->union($q2)->get();
```

Union all:

```php
$rows = $q1->unionAll($q2)->get();
```

---

## Pagination

Klassisk pagination:

```php
$result = User::where('status', '=', 'active')
    ->paginate(perPage: 10, currentPage: 2);
```

Returnerar normalt en array med data och metadata.

Exempel på användning:

```php
$users = $result['data'] ?? [];
$total = $result['total'] ?? 0;
$currentPage = $result['current_page'] ?? 1;
```

Enkel pagination utan full total:

```php
$result = User::simplePaginate(10, 1);
```

---

## Search

Sök över flera kolumner:

```php
$result = User::search(
    term: 'ma',
    searchColumns: ['first_name', 'last_name', 'email'],
    perPage: 10,
    currentPage: 1
);
```

---

## Snabba hämtningar

### `value()`

Första värdet i första raden:

```php
$email = User::where('id', '=', 1)->value('email');
```

### `pluck()`

Lista med värden:

```php
$emails = User::pluck('email');
```

Assoc-lista:

```php
$emailsById = User::pluck('email', 'id');
```

### `scalar()`, `int()`, `float()`, `string()`

Skalära resultat:

```php
$count = User::count('*', 'total')->int();

$name = User::where('id', '=', 1)->string();
```

---

## Raw fetch

Om du vill hämta assoc-arrayer utan modellhydrering:

```php
$rows = User::where('status', '=', 'active')->fetchAllRaw();
```

För första raden:

```php
$row = User::where('id', '=', 1)->fetchRaw();
```

---

## Mutationer

### Insert

```php
User::from('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ])
    ->execute();
```

### Update

```php
User::from('users')
    ->where('id', '=', 1)
    ->update([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ])
    ->execute();
```

### Delete

```php
User::from('users')
    ->where('id', '=', 1)
    ->delete()
    ->execute();
```

`delete()` utan `WHERE` ska undvikas och skyddas av QueryBuilder.

### Insert or ignore

```php
User::from('users')
    ->insertOrIgnore([
        'email' => 'duplicate@example.com',
    ])
    ->execute();
```

### Upsert

```php
User::from('users')
    ->upsert(
        [
            [
                'email' => 'a@example.com',
                'name' => 'A',
            ],
        ],
        uniqueBy: ['email']
    )
    ->execute();
```

---

## Model save

Du kan skapa och spara modellobjekt:

```php
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$user->save();
```

Uppdatera:

```php
$user = User::find(1);

if ($user !== null) {
    $user->name = 'Jane Doe';
    $user->save();
}
```

Ta bort:

```php
$user = User::find(1);

if ($user !== null) {
    $user->delete();
}
```

---

## `firstOrCreate`

Skapa eller hämta första matchande rad:

```php
$user = User::firstOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe']
);
```

---

## `updateOrCreate`

Skapa eller uppdatera första matchande rad:

```php
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],
    ['name' => 'John Doe']
);
```

---

## Accessors

Du kan definiera accessors för attribut.

Exempel:

```php
public function getNameAttribute(mixed $value): string
{
    return trim((string) $value);
}
```

När du läser:

```php
$user->name
```

används accessorn.

---

## Mutators

Du kan definiera mutators för attribut.

Exempel:

```php
public function setEmailAttribute(mixed $value): void
{
    $this->forceFill([
        'email' => strtolower(trim((string) $value)),
    ]);
}
```

När du sätter:

```php
$user->email = 'TEST@EXAMPLE.COM';
```

kan mutatorn normalisera värdet.

---

## `toArray()` och JSON

Modeller kan konverteras till array:

```php
$array = $user->toArray();
```

Modeller kan också JSON-serialiseras:

```php
return $this->json([
    'user' => $user,
]);
```

Relationer som är laddade tas med i `toArray()`.

---

## Soft deletes

Om modellen har:

```php
protected bool $softDeletes = true;
```

filtreras rader med `deleted_at` bort som standard.

Standard:

```php
$user = User::find(1);
```

Inkludera soft-deletade vid `find()`:

```php
$user = User::find(1, withTrashed: true);
```

Query med soft-deletade:

```php
$users = User::withSoftDeletes()->get();
```

Endast soft-deletade:

```php
$users = User::onlyTrashed()->get();
```

Explicit utan trashed:

```php
$users = User::withoutTrashed()->get();
```

Soft delete:

```php
$user = User::find(1);

if ($user !== null) {
    $user->delete();
}
```

Restore:

```php
$user = User::find(1, withTrashed: true);

if ($user !== null) {
    $user->restore();
}
```

Force delete:

```php
$user = User::find(1, withTrashed: true);

if ($user !== null) {
    $user->forceDelete();
}
```

---

## Relationer

Radix ORM stödjer relationer som:

```text
hasOne
hasMany
belongsTo
belongsToMany
hasManyThrough
hasOneThrough
```

Relationer definieras som metoder på modellen.

---

## `hasOne`

```php
use Radix\Database\ORM\Relationships\HasOne;

public function profile(): HasOne
{
    return $this->hasOne(Profile::class, 'user_id');
}
```

Användning:

```php
$profile = $user->profile()->first();
```

---

## `hasMany`

```php
use Radix\Database\ORM\Relationships\HasMany;

public function posts(): HasMany
{
    return $this->hasMany(Post::class, 'user_id');
}
```

Användning:

```php
$posts = $user->posts()->get();
```

---

## `belongsTo`

```php
use Radix\Database\ORM\Relationships\BelongsTo;

public function user(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

Användning:

```php
$user = $post->user()->first();
```

---

## `belongsToMany`

```php
use Radix\Database\ORM\Relationships\BelongsToMany;

public function roles(): BelongsToMany
{
    return $this->belongsToMany(
        Role::class,
        'role_user',
        'user_id',
        'role_id'
    );
}
```

Användning:

```php
$roles = $user->roles()->get();
```

---

## `hasManyThrough`

Exempel:

```php
use Radix\Database\ORM\Relationships\HasManyThrough;

public function votes(): HasManyThrough
{
    return $this->hasManyThrough(
        Vote::class,
        Subject::class,
        'category_id',
        'subject_id'
    );
}
```

---

## `hasOneThrough`

Exempel:

```php
use Radix\Database\ORM\Relationships\HasOneThrough;

public function topVote(): HasOneThrough
{
    return $this->hasOneThrough(
        Vote::class,
        Subject::class,
        'category_id',
        'subject_id'
    );
}
```

---

## Lazy loading av relation

Om relationen finns som metod kan den nås via property-liknande access beroende på modellens magiska getter.

Exempel:

```php
$posts = $user->posts;
```

För tydlighet rekommenderas ofta explicit relation:

```php
$posts = $user->posts()->get();
```

---

## Eager loading

Ladda relationer vid query:

```php
$users = User::with(['profile', 'posts'])->get();
```

Med constraints:

```php
use Radix\Database\QueryBuilder\QueryBuilder;

$users = User::with([
    'posts' => function (QueryBuilder $query): void {
        $query->where('published', '=', 1);
    },
])->get();
```

---

## Ladda relationer på modell

Ladda relationer efter att modellen hämtats:

```php
$user = User::find(1);

if ($user !== null) {
    $user->load('posts');
}
```

Flera relationer:

```php
$user->load(['posts', 'profile']);
```

Med constraint:

```php
use Radix\Database\QueryBuilder\QueryBuilder;

$user->load([
    'posts' => function (QueryBuilder $query): void {
        $query->where('published', '=', 1);
    },
]);
```

---

## `loadMissing()`

Ladda relationer bara om de inte redan är laddade:

```php
$user->loadMissing(['posts', 'profile']);
```

---

## Autoload relations

En modell kan ha relationer som laddas automatiskt:

```php
protected array $autoloadRelations = [
    'profile',
];
```

Använd sparsamt, eftersom autoload kan ge extra queries.

---

## Aggregat över relationer

### `withCount`

```php
$users = User::withCount('posts')->get();
```

Flera relationer:

```php
$users = User::withCount(['posts', 'comments'])->get();
```

### `withCountWhere`

```php
$users = User::withCountWhere(
    relation: 'posts',
    column: 'published',
    value: 1,
    alias: 'published_posts_count'
)->get();
```

### `withSum`

```php
$users = User::withSum('posts', 'views', 'posts_views')->get();
```

### `withAvg`

```php
$users = User::withAvg('posts', 'views', 'posts_avg_views')->get();
```

### `withMin`

```php
$users = User::withMin('posts', 'views', 'posts_min_views')->get();
```

### `withMax`

```php
$users = User::withMax('posts', 'views', 'posts_max_views')->get();
```

### `withAggregate`

```php
$users = User::withAggregate(
    relation: 'posts',
    column: 'views',
    fn: 'SUM',
    alias: 'posts_views_sum'
)->get();
```

---

## Aggregat i SELECT

```php
User::count('*', 'total')->get();

User::avg('age', 'average_age')->get();

User::sum('amount', 'total_amount')->get();

User::min('created_at', 'first_created')->get();

User::max('created_at', 'last_created')->get();
```

Andra helpers kan finnas, till exempel:

```php
User::upper('name', 'upper_name')->get();

User::lower('email', 'lower_email')->get();

User::year('created_at', 'year')->get();

User::month('created_at', 'month')->get();
```

---

## JSON helpers i QueryBuilder

QueryBuilder kan ha stöd för JSON-funktioner.

Exempel:

```php
User::jsonExtract('settings', '$.theme', 'theme')->get();
```

Where JSON contains:

```php
User::whereJsonContains('roles', 'admin')->get();
```

Where JSON path:

```php
User::whereJsonPath('settings', '$.enabled', '=', true)->get();
```

Stöd kan bero på databasdialekt.

---

## CTE

Common Table Expressions kan användas med:

```php
$sub = User::select(['id'])
    ->from('users')
    ->where('status', '=', 'active');

$rows = User::withCte('active_users', $sub)
    ->from('active_users')
    ->get();
```

Raw CTE:

```php
User::withCteRaw(
    'active_users AS (SELECT id FROM users WHERE status = ?)',
    ['active']
)->get();
```

Recursive CTE kan finnas via:

```php
User::withRecursive($name, $anchor, $recursive, $columns);
```

---

## Window functions

Exempel:

```php
$rows = User::from('users')
    ->rowNumber('row_num', partitionBy: ['role'], orderBy: ['created_at'])
    ->get();
```

Andra helpers kan finnas:

```php
rank()
denseRank()
sumOver()
avgOver()
windowRaw()
```

Stöd beror på databasdialekt.

---

## CASE expressions

Exempel:

```php
$users = User::caseWhen(
    [
        ['role', '=', 'admin', 'Admin'],
        ['role', '=', 'editor', 'Editor'],
    ],
    elseExpr: 'User',
    alias: 'role_label'
)->get();
```

Order by case:

```php
$users = User::orderByCase(
    'role',
    [
        'admin' => '1',
        'editor' => '2',
        'user' => '3',
    ],
    default: '99'
)->get();
```

---

## Locks

För databaser som stödjer låsning:

```php
User::where('id', '=', 1)
    ->forUpdate()
    ->get();
```

Share mode:

```php
User::where('id', '=', 1)
    ->lockInShareMode()
    ->get();
```

Raw lock:

```php
User::where('id', '=', 1)
    ->lock('FOR UPDATE')
    ->get();
```

Stöd beror på databasdialekt.

---

## Transaktioner

Transaktion:

```php
User::transaction(function (): void {
    User::from('users')->insert([
        'name' => 'John',
        'email' => 'john@example.com',
    ])->execute();

    User::from('logs')->insert([
        'message' => 'User created',
    ])->execute();
});
```

Manuell transaktion:

```php
User::startTransaction();

try {
    // queries

    User::commitTransaction();
} catch (\Throwable $e) {
    User::rollbackTransaction();

    throw $e;
}
```

---

## Lazy och chunk

Lazy iteration:

```php
foreach (User::where('status', '=', 'active')->lazy(1000) as $user) {
    // ...
}
```

Chunk:

```php
User::where('status', '=', 'active')
    ->chunk(100, function ($users): void {
        foreach ($users as $user) {
            // ...
        }
    });
```

Det är användbart för stora tabeller.

---

## Debugging

Visa SQL med placeholders:

```php
$query = User::where('email', '=', 'john@example.com');

$sql = $query->toSql();
```

Visa bindings:

```php
$bindings = $query->getBindings();
```

Debug SQL med interpolerade värden:

```php
$sql = $query->debugSql();
```

Alternativ:

```php
$sql = $query->getRawSql();
```

Dumpa och fortsätt kedjan:

```php
User::where('email', '=', 'john@example.com')
    ->dump()
    ->first();
```

Använd debug SQL bara i development och logga inte känsliga värden i production.

---

## Bindnings-buckets

QueryBuilder använder separata bindnings-buckets för olika SQL-delar, till exempel:

```text
select
join
where
having
order
union
mutation
```

Det gör att bindningar kan kombineras i rätt ordning när SQL kompileras.

Det är viktigt för till exempel:

- raw select
- joinRaw
- whereRaw
- havingRaw
- subqueries
- update
- unions

---

## Säkerhet

QueryBuilder använder placeholders/bindningar för värden.

Bra:

```php
User::where('email', '=', $email)->first();
```

Undvik att bygga SQL med direkt strängkonkatenering:

```php
User::whereRaw("email = '{$email}'");
```

Använd bindningar:

```php
User::whereRaw('email = ?', [$email])->first();
```

---

## Validering

ORM ersätter inte validering.

Validera alltid input innan du skriver till databasen.

Exempel:

```php
$form = new ContactRequest($this->request);

if (!$form->validate()) {
    return $this->view('contact.index', [
        'errors' => $form->errors(),
    ]);
}
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Mass assignment

Använd `$fillable` och/eller `$guarded` för att kontrollera vilka attribut som får fyllas.

Exempel:

```php
protected array $fillable = [
    'name',
    'email',
];
```

Då ignoreras attribut som inte är tillåtna vid `fill()`.

För intern kod där du medvetet vill sätta allt:

```php
$user->forceFill([
    'email' => 'admin@example.com',
    'role' => 'admin',
]);
```

Använd `forceFill()` med försiktighet.

---

## Collection

`get()` returnerar en Collection.

Exempel:

```php
$users = User::where('status', '=', 'active')->get();

$names = $users->pluck('name')->values()->toArray();

$first = $users->first();
```

Se Collection-API i frameworket om du behöver fler metoder.

---

## Testning

Kör tester:

```bash
composer test
```

Kör statisk analys:

```bash
composer stan
```

För databasnära tester bör du tänka på:

- testdatabas
- rollback/transaction mellan tester
- seeders
- isolerad testdata
- att inte köra mot production

Se mer i:

- [`TESTING.md`](TESTING.md)

---

## Vanliga flöden

### Hämta lista

```php
$users = User::where('status', '=', 'active')
    ->orderBy('name')
    ->get();
```

### Hämta en användare

```php
$user = User::find(1);
```

### Skapa användare

```php
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

$user->save();
```

### Uppdatera användare

```php
$user = User::find(1);

if ($user !== null) {
    $user->name = 'Jane Doe';
    $user->save();
}
```

### Radera användare

```php
$user = User::find(1);

if ($user !== null) {
    $user->delete();
}
```

### API-response

```php
$user = User::find(1);

return $this->json([
    'data' => $user?->toArray(),
]);
```

---

## Felsökning

### Databasanslutning saknas

Kontrollera:

```text
.env
config/database.php
```

Kör:

```bash
php radix migrations:migrate
```

### Modell hittar inte tabell

Kontrollera modellens `$table`:

```php
protected string $table = 'users';
```

### `ModelClassResolverInterface is not configured`

ORM behöver resolver-konfiguration i bootstrap/config.

Kontrollera:

```text
config/orm.php
config/providers.php
bootstrap/
```

### Relation hittas inte

Kontrollera att relationen finns som metod på modellen:

```php
public function posts(): HasMany
{
    return $this->hasMany(Post::class, 'user_id');
}
```

### Relationens modellklass kan inte laddas

Kontrollera:

- namespace
- filnamn
- Composer autoload
- att modellen ärver från `Radix\Database\ORM\Model`

Kör vid behov:

```bash
composer dump-autoload
```

### Soft-deletade rader syns inte

Om modellen använder soft deletes filtreras `deleted_at` bort som standard.

Använd:

```php
User::withSoftDeletes()->get();
```

eller:

```php
User::find(1, withTrashed: true);
```

### Delete tar inte bort raden fysiskt

Om modellen använder soft deletes sätter `delete()` normalt `deleted_at`.

Använd:

```php
$user->forceDelete();
```

om du verkligen vill ta bort raden.

### Raw SQL ger fel bindings

Kontrollera att varje `?` har en motsvarande bindning:

```php
User::whereRaw('email = ? AND status = ?', [$email, 'active'])->get();
```

### Pagination returnerar oväntad struktur

Dumpa resultatet i development:

```php
$result = User::paginate(10, 1);

var_dump($result);
```

Strukturen kan bero på QueryBuilder-version.

---

## Bra praxis

- definiera `$table` tydligt i modeller
- använd `$fillable` för mass assignment
- validera input innan databasoperationer
- använd bindningar i raw queries
- håll controllers tunna och flytta databaslogik till services/modeller
- använd transactions för flera beroende writes
- undvik autoloadade relationer om de ger många extra queries
- använd pagination för stora listor
- använd `chunk()` eller `lazy()` för stora datamängder
- testa migrationer och queries lokalt först

---

## Relaterat

- [`DATABASE.md`](DATABASE.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`SERVICES.md`](SERVICES.md)
- [`API.md`](API.md)
- [`TESTING.md`](TESTING.md)
