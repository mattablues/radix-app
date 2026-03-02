# docs/CONTROLLERS.md

← [`Tillbaka till index`](INDEX.md)

# Controllers (Radix App)

Controllers hanterar inkommande HTTP-förfrågningar och returnerar ett `Response`. De är “limmet” mellan routing, affärslogik (services) och rendering (views/JSON).

---

## Skapa en controller

Det enklaste sättet är via CLI:

```bash
php radix make:controller UserController
```

Det skapar en fil under:

- `src/Controllers/`

Om du vill skapa en controller i en undermapp (t.ex. för API) kan du ange en path:

```bash
php radix make:controller Api/UserController
```

---

## Web controllers: `AbstractController`

Vanliga webb-controllers bör ärva från `Radix\Controller\AbstractController`.

### Exempel (web)

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
        return $this->view('home', [
            'title' => 'Välkommen till Radix',
        ]);
    }
}
```

Det här ger dig bland annat:
- vy-rendering via `$this->view(...)`
- tillgång till request/session via `$this->request`
- en “before hook” för t.ex. CSRF-kontroller (se nedan)

---

## CSRF-skydd för POST/PUT/DELETE

I actions som ändrar data bör du köra:

```php
$this->before();
```

Exempel:

```php
public function store(): Response
{
    $this->before(); // validerar CSRF-token (och ev. annan preflight som din controller bas har)
    // ... spara data ...
    return redirect(route('home.index'));
}
```

> Rekommendation: gör det till en vana att anropa `before()` tidigt i “write”-actions.

---

## API controllers (JSON)

Om du bygger API-endpoints använder du en API-anpassad controller (t.ex. `Radix\Controller\ApiController` om den finns i din setup).

Typiska helpers brukar vara:
- JSON responses (t.ex. `$this->json($data)`)
- validering av inkommande payload och automatiska `422`-svar
- `getJsonPayload()`/liknande för att läsa request-body

> Exakt klassnamn och helpers beror på vilken bascontroller som finns i din Radix-version, men principen är densamma: web => HTML/views, api => JSON.

---

## Dependency Injection (DI) i controllers

Controllers kan ta dependencies i konstruktorn. Då kan du flytta logik till services och hålla controllers tunna.

Exempel:

```php
public function __construct(
    private readonly \App\Services\AuthService $authService,
) {}
```

---

## Bra praxis

- Håll controllers små: “ta in request => kalla service => returnera response”
- Lägg affärslogik i `src/Services/`
- Lägg validering i form requests (t.ex. via `make:form-request`) när det passar
