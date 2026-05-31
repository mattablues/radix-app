# docs/ROUTING.md

← [`Tillbaka till index`](INDEX.md)

# Routing (Radix App)

Radix App använder routern från **Radix Framework**.

Routes definierar hur inkommande HTTP requests matchas mot controllers, handlers eller closures.

En route består normalt av:

```text
HTTP-metod + path + handler
```

Exempel:

```php
$router->get('/about', [\App\Controllers\AboutController::class, 'index'])
    ->name('about.index');
```

---

## Var routes ligger

Route-filer ligger normalt i:

```text
routes/
```

Vanliga route-filer:

```text
routes/web.php
routes/api.php
routes/auth.php
routes/user.php
routes/admin.php
routes/updates.php
routes/api.user.php
routes/api.admin.php
```

Exakt vilka route-filer som finns kan bero på installerade scaffolds.

---

## Route-laddning

Appens route-konfiguration ligger normalt i:

```text
config/routes.php
```

Där skapas routern och route-filer laddas in.

Grundfiler som normalt laddas:

```text
routes/web.php
routes/api.php
```

Scaffold-relaterade route-filer kan laddas villkorligt om de finns, till exempel:

```text
routes/auth.php
routes/user.php
routes/admin.php
routes/updates.php
```

API-routes kan i sin tur ladda fler API-filer, till exempel:

```text
routes/api.user.php
routes/api.admin.php
```

---

## Web routes

Web routes ligger normalt i:

```text
routes/web.php
```

De används för vanliga HTML-sidor.

Exempel:

```php
$router->get('/', [
    \App\Controllers\HomeController::class, 'index',
])->name('home.index');

$router->get('/contact', [
    \App\Controllers\ContactController::class, 'index',
])->name('contact.index');

$router->post('/contact', [
    \App\Controllers\ContactController::class, 'create',
])->name('contact.create');
```

---

## API routes

API routes ligger normalt i:

```text
routes/api.php
```

API-routes grupperas ofta under ett prefix, till exempel:

```text
/api/v1
```

Exempel:

```php
$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/health', [
        \App\Controllers\Api\HealthController::class, 'index',
    ])->name('api.health');
});
```

---

## Grundläggande route

En route registreras med HTTP-metod:

```php
$router->get('/about', [\App\Controllers\AboutController::class, 'index'])
    ->name('about.index');

$router->post('/contact', [\App\Controllers\ContactController::class, 'create'])
    ->name('contact.create');
```

Vanliga metoder:

```php
$router->get('/path', $handler);
$router->post('/path', $handler);
```

Beroende på framework-version kan fler HTTP-metoder finnas, till exempel PUT, PATCH eller DELETE.

---

## Controller-handlers

En vanlig route pekar på en controller-metod:

```php
$router->get('/about', [
    \App\Controllers\AboutController::class,
    'index',
])->name('about.index');
```

Det betyder:

```text
GET /about
  ↓
App\Controllers\AboutController::index()
```

Controllers ligger normalt i:

```text
src/Controllers/
```

Se mer i:

- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## Closures / callables

För enkla routes kan du använda en closure.

```php
$router->get('/hello', function () {
    return response('Hej!');
})->name('hello');
```

Du kan också skapa ett response-objekt själv:

```php
$router->get('/plain', function () {
    $response = new \Radix\Http\Response();
    $response->setBody('Plain response');

    return $response;
})->name('plain');
```

---

## Route-parametrar

Routes kan ha parametrar:

```php
$router->get('/users/{id}', [
    \App\Controllers\UserController::class,
    'show',
])->name('users.show');
```

Exempel på URL:

```text
/users/42
```

Parametern `id` kan skickas vidare till handler/controller beroende på handlerns signatur.

Exempel med closure:

```php
$router->get('/greet/{name}', function (string $name) {
    return response("Hej {$name}!");
})->name('greet');
```

---

## Route-parametrar med regex

Du kan begränsa parametrar med regex direkt i pathen.

Exempel där `id` bara matchar siffror:

```php
$router->get('/users/{id:[\d]+}', [
    \App\Controllers\UserController::class,
    'show',
])->name('users.show');
```

Exempel med token:

```php
$router->get('/password-reset/{token:[\da-f]+}', [
    \App\Controllers\Auth\PasswordResetController::class,
    'index',
])->name('password.reset');
```

Om regex inte matchar blir routen inte vald.

---

## Namngivna routes

Routes kan namnges med:

```php
->name('route.name')
```

Exempel:

```php
$router->get('/contact', [
    \App\Controllers\ContactController::class,
    'index',
])->name('contact.index');
```

Namngivna routes används för att generera URL:er utan att hårdkoda paths.

Exempel:

```php
$url = route('contact.index');
```

Med parametrar:

```php
$url = route('users.show', ['id' => 42]);
```

Det kan ge:

```text
/users/42
```

---

## Varför använda namngivna routes?

Namngivna routes gör det enklare att ändra URL-struktur senare.

I stället för att skriva detta överallt:

```php
'/users/' . $user->id
```

kan du skriva:

```php
route('users.show', ['id' => $user->id])
```

Det är särskilt användbart i:

- controllers
- views/templates
- redirects
- mail
- API-responser

---

## Route-grupper

Grupper används för att dela inställningar mellan flera routes.

Vanliga gruppinställningar:

```text
path
middleware
```

Exempel:

```php
$router->group(['path' => '/admin'], function (\Radix\Routing\Router $router) {
    $router->get('/dashboard', [
        \App\Controllers\Admin\DashboardController::class,
        'index',
    ])->name('admin.dashboard');

    $router->get('/users', [
        \App\Controllers\Admin\UserController::class,
        'index',
    ])->name('admin.users.index');
});
```

Det ger routes som:

```text
/admin/dashboard
/admin/users
```

---

## Middleware på grupper

Middleware kan läggas på en grupp:

```php
$router->group([
    'path' => '/admin',
    'middleware' => ['auth', 'role.admin'],
], function (\Radix\Routing\Router $router) {
    $router->get('/dashboard', [
        \App\Controllers\Admin\DashboardController::class,
        'index',
    ])->name('admin.dashboard');
});
```

Alla routes i gruppen får då samma middleware.

---

## Middleware på enskild route

Middleware kan också läggas på en specifik route:

```php
$router->post('/contact', [
    \App\Controllers\ContactController::class,
    'create',
])->name('contact.create')->middleware(['api.throttle.light']);
```

Det är användbart när bara en specifik route behöver extra skydd, till exempel throttling på ett formulär.

---

## Nestlade grupper

Grupper kan nästlas.

Exempel:

```php
$router->group(['path' => '/api/v1'], function (\Radix\Routing\Router $router) {
    $router->group(['middleware' => ['api.throttle']], function (\Radix\Routing\Router $router) {
        $router->get('/users', [
            \App\Controllers\Api\UserController::class,
            'index',
        ])->name('api.users.index');
    });
});
```

Det ger:

```text
GET /api/v1/users
```

med middleware från den inre gruppen.

---

## Vanliga web-middleware

Web routes kan använda middleware som:

```text
canonical.url
request.id
api.logger
security.headers
limit.web
csrf
```

Exakt vilka middleware-alias som finns beror på appens `config/middleware*.php`.

Exempel:

```php
$router->group([
    'middleware' => [
        'canonical.url',
        'request.id',
        'api.logger',
        'security.headers',
        'limit.web',
        'csrf',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/', [
        \App\Controllers\HomeController::class,
        'index',
    ])->name('home.index');
});
```

Se mer i:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)

---

## Vanliga API-middleware

API routes kan använda middleware som:

```text
request.id
api.logger
security.headers
api.throttle
api.throttle.light
api.throttle.hard
```

Exempel:

```php
$router->group([
    'path' => '/api/v1',
    'middleware' => [
        'request.id',
        'api.logger',
        'security.headers',
    ],
], function (\Radix\Routing\Router $router) {
    $router->get('/health', [
        \App\Controllers\Api\HealthController::class,
        'index',
    ])->name('api.health');
});
```

Se mer i:

- [`API.md`](API.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Preflight / OPTIONS för API

För API:er hanteras CORS ofta via middleware.

Det kan även finnas en catch-all route som returnerar `204 No Content` för `OPTIONS`.

Exempel:

```php
$router->get('/{any:.*}', function () {
    $response = new \Radix\Http\Response();

    $method = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
        ? $_SERVER['REQUEST_METHOD']
        : '';

    if (strtoupper($method) === 'OPTIONS') {
        $response->setStatusCode(204);
        return $response;
    }

    $response->setStatusCode(404);
    return $response;
})->name('api.preflight');
```

---

## Route-filer från scaffolds

Scaffolds kan lägga till route-filer.

Exempel:

```text
routes/auth.php
routes/user.php
routes/admin.php
routes/updates.php
routes/api.user.php
routes/api.admin.php
```

Efter att du installerat ett scaffold kan du behöva rensa cache:

```bash
php radix cache:clear
```

Om scaffoldet lägger till migrations:

```bash
php radix migrations:migrate
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Placeholder-route-filer

En ny/minimal Radix App kan innehålla placeholder-route-filer.

Syftet är att projektet ska vara statiskt analyserbart innan alla scaffolds är installerade.

När du installerar scaffolds i en ny app rekommenderas:

```bash
php radix scaffold:install auth --force-placeholders
```

eller:

```bash
php radix scaffold:install --all --force-placeholders
```

Använd `--force` endast om du medvetet vill skriva över riktiga route-filer.

---

## URL-generering i views

I templates kan du normalt använda route helpers för att bygga URL:er.

Exempel:

```php
<a href="<?= e(route('contact.index')) ?>">Kontakt</a>
```

Med parameter:

```php
<a href="<?= e(route('users.show', ['id' => $user->id])) ?>">
    Visa användare
</a>
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Redirects

I controllers är namngivna routes användbara för redirects.

Exempel:

```php
return redirect(route('contact.index'));
```

eller beroende på controller/helper-stil:

```php
return $this->redirect(route('contact.index'));
```

Se mer i:

- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`HTTP.md`](HTTP.md)

---

## Route-namnkonventioner

Rekommenderad namngivning:

```text
home.index
contact.index
contact.create

auth.login
auth.logout
auth.register

users.index
users.show
users.create
users.store
users.edit
users.update
users.destroy

admin.dashboard
admin.users.index

api.health
api.users.index
```

Försök hålla route-namn:

- konsekventa
- läsbara
- grupperade per område
- stabila även om URL:en ändras

---

## Felsökning

### 404 Not Found

Kontrollera:

- att route-filen laddas
- att pathen matchar exakt
- att HTTP-metoden matchar
- att eventuell regex matchar
- att prefix från route-grupper inte saknas

Exempel:

```text
GET /contact
```

matchar inte:

```text
POST /contact
```

### Route-fil laddas inte

Kontrollera:

```text
config/routes.php
```

och att filen finns i:

```text
routes/
```

Efter ändringar:

```bash
php radix cache:clear
```

### Middleware stoppar requesten

Testa tillfälligt att ta bort middleware från routen/gruppen eller kontrollera middleware-konfigurationen.

Se:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

### Route-parametern skickas inte till handlern

Kontrollera att parameternamnet i pathen matchar handlerns förväntade argument.

Exempel:

```php
$router->get('/users/{id}', function (string $id) {
    return response($id);
});
```

### Regex matchar inte

Den här routen matchar bara siffror:

```php
$router->get('/users/{id:[\d]+}', $handler);
```

Den matchar:

```text
/users/123
```

men inte:

```text
/users/abc
```

---

## Relaterat

- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`HTTP.md`](HTTP.md)
- [`API.md`](API.md)
- [`SECURITY.md`](SECURITY.md)
- [`CLI.md`](CLI.md)
