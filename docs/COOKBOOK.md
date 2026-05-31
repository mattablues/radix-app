# docs/COOKBOOK.md

← [`Tillbaka till index`](INDEX.md)

# Radix Cookbook (Radix App)

Det här dokumentet innehåller snabba recept för vanliga utvecklingsuppgifter i **Radix App**.

För mer detaljer, se respektive dokumentationsfil.

---

## Skapa en ny sida

Ett vanligt flöde för en ny webbsida:

```text
route
  ↓
controller
  ↓
view
```

### 1. Skapa controller

```bash
php radix make:controller PageController
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

final class PageController extends AbstractController
{
    public function about(): Response
    {
        return $this->view('pages.about', [
            'title' => 'Om oss',
        ]);
    }
}
```

### 2. Skapa view

```bash
php radix make:view pages/about
```

Det skapar normalt:

```text
views/pages/about.ratio.php
```

Exempel:

```html
{% extends "layouts/app.ratio.php" %}

{% block title %}Om oss{% endblock %}

{% block body %}
    <h1>Om oss</h1>

    <p>Det här är en ny sida.</p>
{% endblock %}
```

### 3. Lägg till route

I lämplig route-fil, till exempel:

```text
routes/web.php
```

Lägg till:

```php
$router->get('/about-us', [
    \App\Controllers\PageController::class,
    'about',
])->name('pages.about');
```

### 4. Länka till sidan

```html
<a href="{{ route('pages.about') }}">Om oss</a>
```

Se mer:

- [`ROUTING.md`](ROUTING.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Skapa ett formulär

### 1. Skapa form request

```bash
php radix make:form-request ContactRequest
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Requests;

use Radix\Http\FormRequest;

final class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'message' => 'required|string|max:5000',
        ];
    }
}
```

### 2. Skapa controller action

```php
public function create(): \Radix\Http\Response
{
    $this->before();

    $form = new \App\Requests\ContactRequest($this->request);

    if (!$form->validate()) {
        return $this->view('contact.index', [
            'errors' => $form->errors(),
        ]);
    }

    // Hantera formuläret.

    return redirect(route('home.index'));
}
```

### 3. Skapa route

```php
$router->post('/contact', [
    \App\Controllers\ContactController::class,
    'create',
])->name('contact.create');
```

### 4. Skapa template

```html
<form method="post" action="{{ route('contact.create') }}">
    {{ csrf_field()|raw }}

    <label for="email">E-post</label>
    <input id="email" name="email" type="email">

    <label for="message">Meddelande</label>
    <textarea id="message" name="message"></textarea>

    <button type="submit">Skicka</button>
</form>
```

Se mer:

- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Skapa modell, migration och tabell

### 1. Skapa modell

```bash
php radix make:model Post
```

### 2. Skapa migration

```bash
php radix make:migration create posts
```

### 3. Redigera migrationen

Exempel:

```php
$schema->create('posts', function (\Radix\Database\Migration\Table $table): void {
    $table->id();
    $table->string('title');
    $table->text('body');
    $table->timestamps();
});
```

### 4. Kör migrationer

```bash
php radix migrations:migrate
```

### 5. Använd modellen

```php
$posts = \App\Models\Post::orderByDesc('created_at')->get();
```

Se mer:

- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)

---

## Skapa API endpoint

### 1. Skapa controller

```bash
php radix make:controller Api/PostController
```

### 2. Controller-exempel

```php
<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Radix\Http\JsonResponse;

final class PostController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->validateRequest();

        $posts = \App\Models\Post::orderByDesc('created_at')->get();

        return $this->json([
            'success' => true,
            'data' => $posts->map(
                static fn($post): array => $post->toArray()
            )->values()->toArray(),
        ]);
    }
}
```

### 3. Lägg till route

I API-route-fil:

```php
$router->get('/posts', [
    \App\Controllers\Api\PostController::class,
    'index',
])->name('api.posts.index');
```

Under `/api/v1` blir endpointen:

```text
GET /api/v1/posts
```

### 4. Testa med curl

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost/api/v1/posts
```

Se mer:

- [`API.md`](API.md)
- [`HTTP.md`](HTTP.md)
- [`ROUTING.md`](ROUTING.md)

---

## Lägga till ett nytt CLI-kommando

Radix App har app-specifikt stöd för egna CLI-kommandon.

### Skapa kommando

```bash
php radix make:command UsersSyncCommand
```

### Välj CLI-namn själv

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

### Skippa automatisk config

```bash
php radix make:command UsersSyncCommand --no-config
```

Då behöver du registrera kommandot manuellt i:

```text
config/commands.php
```

### Kontrollera lista

```bash
php radix
```

Om kommandot inte syns:

```bash
php radix cache:clear
```

Se mer:

- [`CLI.md`](CLI.md)

---

## Installera ett scaffold i ny app

Rekommenderat i ny/minimal app:

```bash
php radix scaffold:install auth --force-placeholders
php radix migrations:migrate
```

För alla top-level presets:

```bash
php radix scaffold:install --all --force-placeholders
php radix migrations:migrate
```

Se vad som skulle hända först:

```bash
php radix scaffold:install auth --dry-run
```

Använd `--force` bara när du medvetet vill skriva över riktiga filer:

```bash
php radix scaffold:install auth --force
```

Se mer:

- [`CLI.md`](CLI.md)

---

## Rensa cache

Efter ändringar i config, templates, routes, providers eller scaffolds:

```bash
php radix cache:clear
```

Se mer:

- [`CACHE.md`](CACHE.md)

---

## Bygga frontend

### Development

```bash
npm run start:dev
```

### Production build

```bash
npm run start:build
```

Det bygger normalt:

```text
public/assets/css/app.css
public/assets/js/app.js
```

Se mer:

- [`FRONTEND.md`](FRONTEND.md)

---

## Lägga till en komponent

Skapa fil:

```text
views/components/ui/badge.ratio.php
```

Exempel:

```html
{% props([
    'type' => 'info'
]) %}

<span class="inline-flex rounded px-2 py-1 text-xs badge-{{ $type }}">
    {{ slot }}
</span>
```

Använd:

```html
<x-ui.badge type="success">
    Aktiv
</x-ui.badge>
```

Se mer:

- [`TEMPLATES.md`](TEMPLATES.md)
- [`FRONTEND.md`](FRONTEND.md)

---

## Ladda upp avatar

### 1. Formulär

```html
<form method="post" enctype="multipart/form-data" action="{{ route('user.avatar.update') }}">
    {{ csrf_field()|raw }}

    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">

    <button type="submit">Ladda upp</button>
</form>
```

### 2. Validering

```php
$validator = new \Radix\Support\Validator([
    'avatar' => $this->request->files['avatar'] ?? null,
], [
    'avatar' => 'required|file_type:image/jpeg,image/png,image/webp|file_size:2',
]);
```

### 3. Upload och crop

```php
$upload = new \Radix\File\Upload(
    $this->request->files['avatar'],
    ROOT_PATH . '/public/uploads/users/' . $user->id
);

$path = $upload->processImage(function (\Radix\File\Image $image): void {
    $image->resizeImage(200, 200, 'crop');
});
```

### 4. Spara publik path

```php
$publicPath = '/uploads/users/' . $user->id . '/' . basename($path);

$user->avatar = $publicPath;
$user->save();
```

Se mer:

- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Skicka mail via event

### 1. Skapa event

```bash
php radix make:event UserRegisteredEvent
```

### 2. Skapa listener

```bash
php radix make:listener SendActivationEmailListener
```

### 3. Registrera listener

I listener-config:

```php
return [
    \App\Events\UserRegisteredEvent::class => [
        [
            'listener' => \App\EventListeners\SendActivationEmailListener::class,
            'type' => 'custom',
            'dependencies' => [\Radix\Mailer\MailManager::class],
        ],
    ],
];
```

### 4. Dispatcha event

```php
$this->events->dispatch(new \App\Events\UserRegisteredEvent(
    email: $user->email,
    firstName: $user->first_name,
    activationLink: $activationLink,
));
```

Se mer:

- [`EVENTS.md`](EVENTS.md)
- [`MAIL.md`](MAIL.md)

---

## Läsa CSV-import

```php
\Radix\File\Reader::csvStream(
    ROOT_PATH . '/storage/import/users.csv',
    function (array $row): void {
        $validator = new \Radix\Support\Validator($row, [
            'email' => 'required|email',
            'name' => 'required|max:100',
        ]);

        if (!$validator->validate()) {
            return;
        }

        \App\Models\User::updateOrCreate(
            ['email' => $row['email']],
            ['name' => $row['name']]
        );
    },
    hasHeader: true
);
```

Se mer:

- [`FILES.md`](FILES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`ORM.md`](ORM.md)

---

## Skriva CSV-export

```php
\Radix\File\Writer::csvStream(
    ROOT_PATH . '/storage/export/users.csv',
    function (callable $writeRow): void {
        foreach (\App\Models\User::orderBy('id')->lazy(1000) as $user) {
            $writeRow([
                $user->id,
                $user->email,
            ]);
        }
    },
    headers: ['ID', 'Email']
);
```

Se mer:

- [`FILES.md`](FILES.md)
- [`ORM.md`](ORM.md)

---

## Använda cache

```php
$cache = new \Radix\Support\FileCache(ROOT_PATH . '/cache/app');

$stats = $cache->get('dashboard_stats');

if ($stats === null) {
    $stats = [
        'users' => \App\Models\User::count('*', 'total')->int(),
    ];

    $cache->set('dashboard_stats', $stats, 300);
}
```

Se mer:

- [`CACHE.md`](CACHE.md)

---

## Logga händelse

```php
$logger = new \Radix\Support\Logger('app');

$logger->info('User {user_id} updated profile', [
    'user_id' => $user->id,
]);
```

Se mer:

- [`LOGGING.md`](LOGGING.md)

---

## Skapa service

```bash
php radix make:service ReportService
```

Exempel:

```php
<?php

declare(strict_types=1);

namespace App\Services;

final class ReportService
{
    public function generate(): array
    {
        return [
            'users' => \App\Models\User::count('*', 'total')->int(),
        ];
    }
}
```

Registrera i provider eller `config/services.php` om den behöver explicit wiring.

Se mer:

- [`SERVICES.md`](SERVICES.md)

---

## Skapa middleware

```bash
php radix make:middleware RequireActiveSubscription
```

Registrera alias i middleware-config:

```php
return [
    'subscription.active' => \App\Middlewares\RequireActiveSubscription::class,
];
```

Använd på route:

```php
$router->get('/billing', [
    \App\Controllers\BillingController::class,
    'index',
])->middleware(['auth', 'subscription.active']);
```

Se mer:

- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Deployment snabbchecklista

### 1. Kontrollera production-env

```dotenv
APP_ENV=production
APP_DEBUG=0
APP_URL=https://example.com
RADIX_DEPLOY=0
MAIL_DEBUG=0
SESSION_COOKIE_SECURE=true
HEALTH_REQUIRE_TOKEN=1
```

### 2. Installera dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
```

Alternativt i CI/CD:

```bash
npm ci
```

### 3. Bygg assets

```bash
npm run start:build
```

### 4. Rensa cache

```bash
php radix cache:clear
```

### 5. Kör migrationer vid behov

```bash
php radix migrations:migrate
```

### 6. Kontrollera rättigheter

PHP/webbservern behöver kunna skriva till:

```text
cache/
storage/
public/uploads/
```

Se mer:

- [`SECURITY.md`](SECURITY.md)
- [`CACHE.md`](CACHE.md)
- [`DATABASE.md`](DATABASE.md)

---

## Utvecklingsflöde före commit

Kör gärna:

```bash
composer format:check
composer stan
composer test
```

Autoformat:

```bash
composer format
```

Valfritt:

```bash
composer infect
composer infect:pcov
composer infect:xdebug
```

Se mer:

- [`TESTING.md`](TESTING.md)

---

## Uppdatera dokumentationens TOC

För README:

```bash
npm run toc
```

Om du använder doctoc manuellt för fler filer:

```bash
npx doctoc docs/INDEX.md --maxlevel 3
```

---

## Felsökning: ändring syns inte

Rensa cache:

```bash
php radix cache:clear
```

Bygg frontend igen:

```bash
npm run start:build
```

Kontrollera även webbläsarcache.

---

## Felsökning: route hittas inte

Kontrollera:

- att route-filen laddas
- att HTTP-metod matchar
- att route-prefix stämmer
- att cache är rensad

```bash
php radix cache:clear
```

Se:

- [`ROUTING.md`](ROUTING.md)

---

## Felsökning: dependency injection misslyckas

Kontrollera:

- att servicen finns
- att namespace stämmer
- att den är registrerad i container/provider om den inte kan autowiras
- att constructor-dependencies finns i containern

```bash
composer dump-autoload
php radix cache:clear
```

Se:

- [`SERVICES.md`](SERVICES.md)

---

## Relaterat

- [`INSTALLATION.md`](INSTALLATION.md)
- [`CLI.md`](CLI.md)
- [`ROUTING.md`](ROUTING.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`DATABASE.md`](DATABASE.md)
- [`ORM.md`](ORM.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)
- [`TESTING.md`](TESTING.md)
