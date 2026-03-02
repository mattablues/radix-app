# docs/ROUTING.md

← [`Tillbaka till index`](INDEX.md)

# Routing (Radix App)

Radix använder en router som stödjer:

- routes för web och API
- route-grupper och prefix
- parametrar (inkl. regex)
- namngivna routes
- middleware per route eller per grupp

Routes definieras typiskt under:

- `routes/web.php`
- `routes/api.php`

---

## Grundläggande routes

En route består av en metod, en path och en handler (controller-metod eller callable).

```php
$router->get('/about', [\App\Controllers\AboutController::class, 'index'])
    ->name('about.index');

$router->post('/contact', [\App\Controllers\ContactController::class, 'create'])
    ->name('contact.create');
```

---

## Closures / callables

För enkla routes kan du använda en closure istället för en controller.

```php
$router->get('/hello', function ($request, $response) {
    return $response->setBody("Hej! Du besöker " . $request->uri);
});
```

### Argument-mappning från URL-parametrar

Routern kan mappa parametrar från URL:en till din handler.

```php
$router->get('/greet/{name}', function ($name) {
    return response("Hej $name!");
});
```

---

## Route-parametrar med regex

Du kan begränsa parametrar med regex direkt i routen.

```php
// Matchar bara siffror
$router->get('/user/{id:[\d]+}/show', [\App\Controllers\UserController::class, 'show'])
    ->name('user.show');

// Matchar en hex-token
$router->get('/password-reset/{token:[\da-f]+}', [\App\Controllers\Auth\PasswordResetController::class, 'index']);
```

---

## Route-grupper och prefix

Grupper används för att dela inställningar (t.ex. prefix och middleware).

```php
$router->group(['path' => '/admin', 'middleware' => ['auth', 'role.admin']], function ($router) {
    $router->get('/dashboard', [\App\Controllers\Admin\Dashboard::class, 'index']);
    $router->get('/users', [\App\Controllers\Admin\UserController::class, 'index']);
});
```

### Nestlade grupper (exempel)

```php
$router->group(['path' => '/api/v1'], function ($router) {
    $router->group(['middleware' => ['api.throttle']], function ($router) {
        $router->get('/users', [\App\Controllers\Api\UserController::class, 'index']);
    });
});
```

---

## Middleware på routes

Middleware kan läggas på en enskild route eller på en grupp.

```php
$router->post('/login', [\App\Controllers\Auth\LoginController::class, 'create'])
    ->middleware(['api.throttle.hard']);
```

---

## Namngivna routes och URL-generering

Namngivna routes gör att du kan generera URL:er med `route()` istället för att hårdkoda paths.

```php
$url = route('user.show', ['id' => 42]); // t.ex. "/user/42/show"
```

Det är särskilt användbart i:
- controllers
- templates/views
- redirects

---

## Preflight / OPTIONS (API)

För API:er hanteras CORS ofta via middleware, men en “catch-all” för OPTIONS kan också förekomma:

```php
$router->get('/{any:.*}', function () {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        return response('')->setStatusCode(204);
    }

    return response('Not Found')->setStatusCode(404);
})->name('api.preflight');
```

---

## Felsökning

- **404 Not Found**: kontrollera att HTTP-metoden matchar (GET/POST osv.)
- **Argument-missmatch**: handler/controller tar argument som inte finns i URL:en
- **Regex-missmatch**: t.ex. `{id:[\d]+}` matchar inte `/user/abc`
