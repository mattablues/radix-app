# Routing i Radix Framework

Radix använder en kraftfull router som stödjer grupper, parametrar med regex, namngivna rutter och middleware-pipelines. Rutter definieras i `routes/web.php` och `routes/api.php`.

## Grundläggande rutter

En rutt består av en metod, en sökväg och en handler (en funktion eller en controller-metod).

```php
$router->get('/about', [\App\Controllers\AboutController::class, 'index'])->name('about.index');

$router->post('/contact', [\App\Controllers\ContactController::class, 'create'])->name('contact.create');
```

## Closures och Callables

För enkla rutter kan du använda en anonym funktion (Closure) istället för en Controller. Routern injicerar automatiskt `Request` och `Response` om du anger dem som argument.

```php
$router->get('/hello', function ($request, $response) {
    return $response->setBody("Hej! Du besöker " . $request->uri);
});
```

### Argument-mappning
Routern mappar automatiskt parametrar från URL:en till funktionens argument:

```php
$router->get('/greet/{name}', function ($name) {
    return response("Hej $name!");
});
```

## Route-parametrar

Du kan använda parametrar i sökvägen. Du kan även begränsa dem med reguljära uttryck direkt i definitionen.

```php
// Matchar bara om {id} är siffror (\d+)
$router->get('/user/{id:[\d]+}/show', [\App\Controllers\UserController::class, 'show'])->name('user.show');

// Matchar en hex-token
$router->get('/password-reset/{token:[\da-f]+}', [\App\Controllers\Auth\PasswordResetController::class, 'index']);
```

## Grupper och Prefix

Använd grupper för att dela inställningar som sökvägar eller middleware mellan flera rutter.

```php
$router->group(['path' => '/admin', 'middleware' => ['auth', 'role.admin']], function ($router) {
    $router->get('/dashboard', [\App\Controllers\Admin\Dashboard::class, 'index']);
    $router->get('/users', [\App\Controllers\Admin\UserController::class, 'index']);
});
```

### Nestlade grupper
Grupper kan nestlas för att bygga komplexa API-strukturer:
```php
$router->group(['path' => '/api/v1'], function ($router) {
    $router->group(['middleware' => ['api.throttle']], function ($router) {
        $router->get('/users', [\App\Controllers\Api\UserController::class, 'index']);
    });
});
```

## Middleware

Middleware kan läggas på en hel grupp eller på en enskild rutt. De körs i den ordning de definieras.

```php
$router->post('/login', [\App\Controllers\Auth\LoginController::class, 'create'])
    ->middleware(['api.throttle.hard']);
```

## Namngivna rutter och URL-generering

Genom att ge en rutt ett namn kan du generera URL:er dynamiskt i koden eller vyerna med hjälpmedlet `route()`.

```php
// I en controller eller vy
$url = route('user.show', ['id' => 42]); // Returnerar "/user/42/show"
```

## Preflight / OPTIONS (API)

För API:er hanteras CORS ofta via en "catch-all" rutt som svarar på OPTIONS-anrop:

```php
$router->get('/{any:.*}', function () {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        return response('')->setStatusCode(204);
    }
    return response('Not Found')->setStatusCode(404);
})->name('api.preflight');
```

## Felsökning

- **404 Not Found**: Kontrollera att HTTP-metoden matchar (t.ex. att du inte skickar POST till en GET-rutt).
- **Argument-missmatch**: Om din funktion/controller-metod kräver argument som inte finns i URL:en kommer ett fel att kastas.
- **Regex-missmatch**: Om du använder t.ex. `{id:[\d]+}`, kommer `/user/abc` inte att matcha.