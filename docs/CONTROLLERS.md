# docs/CONTROLLERS.md

← [`Tillbaka till index`](INDEX.md)

# Controllers (Radix App)

Controllers hanterar inkommande HTTP requests och returnerar responses.

De fungerar som länken mellan:

```text
routes
  ↓
controllers
  ↓
services / models / events
  ↓
response / view / JSON
```

I Radix App ligger controllers normalt under:

```text
src/Controllers/
```

---

## Grundprincip

En controller bör vara relativt tunn.

Den bör främst:

1. ta emot request
2. validera input eller använda en request/form request
3. anropa services/modeller
4. dispatcha events vid behov
5. returnera response, redirect, view eller JSON

Undvik att lägga tung affärslogik direkt i controllers.

Lägg hellre sådan logik i:

```text
src/Services/
src/Models/
src/Events/
src/Listeners/
src/Requests/
```

---

## Var controllers ligger

Vanlig struktur:

```text
src/
  Controllers/
    HomeController.php
    ContactController.php
    UserController.php

    Auth/
      LoginController.php
      LogoutController.php
      RegisterController.php

    Admin/
      UserController.php
      SystemController.php

    Api/
      ApiController.php
      UserController.php
      HealthController.php
```

Exakt struktur beror på installerade scaffolds.

---

## Skapa en controller

Det enklaste sättet är via CLI:

```bash
php radix make:controller UserController
```

Det skapar normalt en controller under:

```text
src/Controllers/
```

För undermappar kan du ange path:

```bash
php radix make:controller Api/UserController
php radix make:controller Admin/UserController
```

Kör hjälp om du vill se exakt vilka argument och options som stöds:

```bash
php radix make:controller --help
```

---

## Web controllers

Vanliga webbcontrollers bör ärva från:

```php
Radix\Controller\AbstractController
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class HomeController extends AbstractController
{
    public function index(): Response
    {
        return $this->view('home.index', [
            'title' => 'Välkommen till Radix',
        ]);
    }
}
```

En web controller returnerar ofta:

- HTML via `$this->view(...)`
- redirect
- plain response
- error response

---

## Koppla controller till route

En route pekar normalt på en controller-metod:

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

Se mer i:

- [`ROUTING.md`](ROUTING.md)

---

## Rendera en view

I en controller kan du returnera en template/view:

```php
public function index(): Response
{
    return $this->view('home.index', [
        'latestVersion' => 'v1.1.7',
    ]);
}
```

View-namnet:

```text
home.index
```

motsvarar normalt en template under:

```text
views/home/index.ratio.php
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Formulär och write-actions

Actions som ändrar data bör skyddas.

Exempel på write-actions:

```text
store
create
update
destroy
delete
logout
```

I webbcontrollers bör du normalt anropa:

```php
$this->before();
```

tidigt i sådana actions.

Exempel:

```php
public function create(): Response
{
    $this->before();

    // validera input
    // spara data
    // returnera redirect eller response
}
```

`before()` kan hantera controllerns preflight-logik, till exempel CSRF-kontroll beroende på appens setup.

Se även:

- [`SECURITY.md`](SECURITY.md)
- [`VALIDATION.md`](VALIDATION.md)

---

## Exempel: formulärcontroller

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Requests\ContactRequest;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class ContactController extends AbstractController
{
    public function index(): Response
    {
        return $this->view('contact.index');
    }

    public function create(): Response
    {
        $this->before();

        $form = new ContactRequest($this->request);

        if (!$form->validate()) {
            return $this->view('contact.index', [
                'errors' => $form->errors(),
            ]);
        }

        // Spara, skicka mail eller dispatcha event här.

        return redirect(route('home.index'));
    }
}
```

---

## Form requests / request validators

För validering kan appen använda request-klasser, ofta under:

```text
src/Requests/
```

Exempel:

```php
$form = new ContactRequest($this->request);

if (!$form->validate()) {
    return $this->view('contact.index', [
        'errors' => $form->errors(),
    ]);
}
```

Skapa form request via CLI om generatorn finns i din app:

```bash
php radix make:form-request ContactRequest
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Redirects

Controllers returnerar ofta redirects efter lyckade POST-requests.

Exempel:

```php
return redirect(route('home.index'));
```

eller beroende på controller/helper-stil:

```php
return $this->redirect(route('home.index'));
```

Rekommenderat mönster:

```text
POST -> validera -> utför ändring -> redirect
```

Detta följer Post/Redirect/Get-mönstret och minskar risken för dubbelpostning vid refresh.

---

## Flash messages och old input

Appen kan använda sessions för att spara:

- flash messages
- valideringsfel
- old input

Exempel på old input:

```php
$this->request->session()->set('old', [
    'email' => $form->email(),
]);
```

Hur flash helpers är uppbyggda kan bero på appens scaffolds.

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`SESSION`](HTTP.md)

---

## Dependency Injection i controllers

Controllers kan få dependencies via konstruktorn.

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(): Response
    {
        return $this->view('users.index', [
            'users' => $this->users->all(),
        ]);
    }
}
```

Det gör controllers lättare att testa och håller affärslogik utanför controllern.

Se mer i:

- [`SERVICES.md`](SERVICES.md)

---

## Events från controllers

Controllers kan dispatcha events när något viktigt händer.

Exempel:

```php
$this->eventDispatcher->dispatch(new ContactFormEvent(
    email: $form->email(),
    message: $form->message(),
));
```

Det gör att controllerlogiken kan vara enkel medan listeners hanterar sidoeffekter, till exempel:

- skicka mail
- skriva logg
- uppdatera statistik
- notifiera admin

Se mer i:

- [`EVENTS.md`](EVENTS.md)

---

## API controllers

API controllers returnerar normalt JSON i stället för HTML.

En app kan ha en egen API-bascontroller, till exempel:

```text
src/Controllers/Api/ApiController.php
```

Den kan i sin tur ärva från frameworkets API-controller:

```php
Radix\Controller\AbstractApiController
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Controller\AbstractApiController;

abstract class ApiController extends AbstractApiController
{
    protected function now(): int
    {
        return time();
    }
}
```

---

## Exempel: API-controller

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Http\JsonResponse;

final class HealthController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
        ]);
    }
}
```

API controllers kan hantera:

- JSON responses
- token-kontroll
- payload-validering
- API-fel
- statuskoder

Se mer i:

- [`API.md`](API.md)
- [`HTTP.md`](HTTP.md)

---

## Return types

Använd tydliga return types när det går.

Vanligt:

```php
use Radix\Http\Response;

public function index(): Response
{
    return $this->view('home.index');
}
```

För API:

```php
use Radix\Http\JsonResponse;

public function index(): JsonResponse
{
    return $this->json(['status' => 'ok']);
}
```

Om en action kan returnera olika response-typer kan du behöva använda en gemensam bastyp eller justera designen.

---

## Request access

I controllers som ärver från Radix bascontroller finns normalt request tillgänglig via:

```php
$this->request
```

Exempel:

```php
$email = (string) $this->request->input('email');
```

eller beroende på request-API:

```php
$email = (string) $this->request->post('email');
```

Se aktuell request-klass och appens helpers för exakt API.

Se mer i:

- [`HTTP.md`](HTTP.md)

---

## Sessions i controllers

Om session är aktiv kan du komma åt den via requesten.

Exempel:

```php
$this->request->session()->set('old', [
    'email' => $email,
]);
```

Typiska användningar:

- flash messages
- old input
- auth state
- CSRF-token
- redirect state

Se mer i:

- [`HTTP.md`](HTTP.md)
- [`SECURITY.md`](SECURITY.md)

---

## Controller concerns / traits

Appen kan använda traits för att dela controllerhjälpare.

Exempelstruktur:

```text
src/Controllers/Concerns/
```

Det är användbart för återkommande formulärlogik, flash-hantering eller liknande.

Rekommendation:

- håll traits små
- undvik att gömma för mycket affärslogik i traits
- flytta större logik till services

---

## Admin controllers

Admin controllers ligger ofta under:

```text
src/Controllers/Admin/
```

De bör skyddas med middleware, till exempel:

```text
auth
role.admin
```

Exempel på route-grupp:

```php
$router->group([
    'path' => '/admin',
    'middleware' => ['auth', 'role.admin'],
], function (\Radix\Routing\Router $router) {
    $router->get('/users', [
        \App\Controllers\Admin\UserController::class,
        'index',
    ])->name('admin.users.index');
});
```

Se mer i:

- [`ROUTING.md`](ROUTING.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)

---

## Auth controllers

Auth controllers ligger ofta under:

```text
src/Controllers/Auth/
```

Exempel:

```text
LoginController
LogoutController
RegisterController
PasswordForgotController
PasswordResetController
```

De installeras eller uppdateras ofta via auth-scaffold.

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`CLI.md`](CLI.md)

---

## Generatorer och scaffolds

Controllerfiler kan skapas på två sätt:

### Via generator

```bash
php radix make:controller UserController
```

### Via scaffold

```bash
php radix scaffold:install auth --force-placeholders
```

Scaffolds kan lägga till flera controllers, routes, views, requests och migrations samtidigt.

Se mer i:

- [`CLI.md`](CLI.md)

---

## Testa controllers

Controllers kan testas via:

- enhetstester där dependencies mockas
- integrationstester mot routing/request/response
- feature-liknande tester om appens testsetup stödjer det

Kör tester:

```bash
composer test
```

Kör statisk analys:

```bash
composer stan
```

Se mer i:

- [`TESTING.md`](TESTING.md)

---

## Bra praxis

### Håll controllers tunna

Bra:

```text
Controller -> Service -> Model/Repository
```

Sämre:

```text
Controller -> all affärslogik direkt i action
```

### Validera input tidigt

```php
$form = new ContactRequest($this->request);

if (!$form->validate()) {
    return $this->view('contact.index', [
        'errors' => $form->errors(),
    ]);
}
```

### Använd redirects efter POST

```php
return redirect(route('home.index'));
```

### Använd services för affärslogik

```php
$this->users->create($data);
```

### Använd events för sidoeffekter

```php
$this->eventDispatcher->dispatch(new UserRegisteredEvent($user));
```

### Returnera tydliga responses

```php
return $this->view('users.index', [
    'users' => $users,
]);
```

eller:

```php
return $this->json([
    'data' => $users,
]);
```

---

## Felsökning

### Controller hittas inte

Kontrollera:

- namespace
- filnamn
- klassnamn
- Composer autoload
- route-definition

Kör vid behov:

```bash
composer dump-autoload
```

### Method hittas inte

Kontrollera att route pekar på rätt metod:

```php
$router->get('/users', [
    \App\Controllers\UserController::class,
    'index',
]);
```

och att controllern har:

```php
public function index(): Response
{
    // ...
}
```

### Dependency injection misslyckas

Kontrollera att servicen är registrerad i containern/config.

Se:

- [`SERVICES.md`](SERVICES.md)
- [`CONFIG.md`](CONFIG.md)

### View hittas inte

Kontrollera att view-namnet matchar filen.

Exempel:

```php
return $this->view('home.index');
```

bör motsvara ungefär:

```text
views/home/index.ratio.php
```

Rensa cache:

```bash
php radix cache:clear
```

### CSRF eller middleware stoppar requesten

Kontrollera:

- att formuläret skickar CSRF-token
- att routen har rätt middleware
- att `$this->before()` används där det behövs
- att session fungerar

Se:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SECURITY.md`](SECURITY.md)

---

## Relaterat

- [`ROUTING.md`](ROUTING.md)
- [`HTTP.md`](HTTP.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)
- [`SERVICES.md`](SERVICES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`API.md`](API.md)
- [`TESTING.md`](TESTING.md)
