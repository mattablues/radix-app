# docs/TEMPLATES.md

← [`Tillbaka till index`](INDEX.md)

# Templates (Radix App)

Radix App använder template-systemet från **Radix Framework** via `RadixTemplateViewer`.

Template-filer använder filändelsen:

```text
.ratio.php
```

Vanliga templates ligger under:

```text
views/
```

---

## Översikt

Radix templates kombinerar:

- PHP
- auto-escaping
- layouts
- blocks/yields
- includes
- komponenter
- props
- slots
- cache av kompilerade templates

Exempel på struktur:

```text
views/
  layouts/
    app.ratio.php
    auth.ratio.php
    email.ratio.php

  components/
    alert.ratio.php
    card.ratio.php
    flash.ratio.php
    ui/

  home/
    index.ratio.php

  contact/
    index.ratio.php

  auth/
    login/
    register/

  admin/
  user/
  emails/
  errors/
```

Exakt struktur beror på installerade scaffolds.

---

## Rendera en template från controller

I en web controller kan du normalt använda:

```php
return $this->view('home.index');
```

Med data:

```php
return $this->view('home.index', [
    'latestVersion' => 'v1.1.7',
]);
```

View-namnet:

```text
home.index
```

motsvarar normalt:

```text
views/home/index.ratio.php
```

Se mer i:

- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## Template-syntax

### Escapad output

Skriv ut en variabel:

```html
{{ $name }}
```

Output escapas normalt för att minska risken för XSS.

Exempel:

```html
<h1>Hej {{ $user->name }}</h1>
```

---

## Rå HTML med `|raw`

Om du vill skriva ut HTML utan escaping:

```html
{{ $html_content | raw }}
```

Använd `|raw` med försiktighet.

Det ska bara användas när innehållet är säkert, till exempel:

- HTML du själv genererat
- sanerat innehåll
- helpers som redan returnerar säker HTML

Exempel:

```html
{{ csrf_field()|raw }}
```

Använd aldrig `|raw` direkt på osäker användarinput.

---

## PHP-direktiv

Du kan skriva PHP-liknande kontrollstrukturer i `{% ... %}`.

Exempel:

```html
{% foreach ($items as $item): %}
    <li>{{ $item }}</li>
{% endforeach; %}
```

If-sats:

```html
{% if($currentUser->isAdmin()) : %}
    <a href="{{ route('admin.dashboard') }}">Admin</a>
{% endif; %}
```

Tilldelning:

```html
{% $theme = 'app'; %}
```

---

## Layouts

Layouts ligger normalt i:

```text
views/layouts/
```

Exempel:

```text
views/layouts/app.ratio.php
views/layouts/auth.ratio.php
views/layouts/email.ratio.php
views/layouts/main.ratio.php
views/layouts/sidebar.ratio.php
```

En layout innehåller gemensam HTML-struktur, till exempel:

- doctype
- `<html>`
- `<head>`
- assets
- header
- navigation
- footer
- scripts
- yield-sektioner

---

## `extends`

En vy kan ärva från en layout:

```html
{% extends "layouts/app.ratio.php" %}
```

Exempel:

```html
{% extends "layouts/app.ratio.php" %}

{% block title %}Startsida{% endblock %}

{% block body %}
    <h1>Välkommen!</h1>
{% endblock %}
```

---

## `yield`

Layouts använder `yield` för att markera var en vy kan fylla in innehåll.

Exempel i layout:

```html
<title>{% yield title %}</title>

<main>
    {% yield body %}
</main>
```

Vy:

```html
{% block title %}Startsida{% endblock %}

{% block body %}
    <h1>Välkommen!</h1>
{% endblock %}
```

---

## Blocks

En vy definierar blocks som layouten kan använda.

Exempel:

```html
{% block pageId %}home{% endblock %}

{% block pageClass %}page-home{% endblock %}

{% block title %}Hem{% endblock %}

{% block body %}
    <h1>Hem</h1>
{% endblock %}
```

Vanliga blocks i app-layouts kan vara:

```text
title
body
pageId
pageClass
alpinejs
```

Exakta blocknamn beror på layouten.

---

## Includes

Du kan inkludera mindre fragment:

```html
{% include "components/flash.ratio.php" %}
```

Exempel:

```html
{% include "components/noscript.ratio.php" %}
```

Includes är bra för:

- navigation
- flash messages
- modaler
- cookie consent
- återanvända HTML-fragment

---

## Komponenter

Komponenter ligger normalt under:

```text
views/components/
```

De kan användas med `<x-...>`-syntax.

Exempelstruktur:

```text
views/components/
  alert.ratio.php
  card.ratio.php
  flash.ratio.php
  ui/
    card.ratio.php
```

---

## Enkel komponent

Komponent:

```html
<div class="rounded-lg border p-4">
    {{ slot }}
</div>
```

Användning:

```html
<x-card>
    <p>Innehåll i kortet.</p>
</x-card>
```

---

## Komponent med attribut

Komponent:

```html
<div class="alert alert-{{ $type }}">
    {{ slot }}
</div>
```

Användning:

```html
<x-alert type="danger">
    Något gick fel!
</x-alert>
```

---

## Props

Komponenter kan deklarera props med:

```html
{% props([
    'title',
    'shadow' => 'shadow-sm',
    'padding' => 'p-6'
]) %}
```

Exempelkomponent:

```html
{% props([
    'title',
    'shadow' => 'shadow-sm',
    'padding' => 'p-6'
]) %}

<div class="bg-white border border-gray-200 rounded-2xl {{ $shadow }} {{ $padding }}">
    <h4 class="text-lg font-bold mb-2">{{ $title }}</h4>
    {{ slot }}
</div>
```

Användning:

```html
<x-card title="Profil">
    <p>Innehåll i kortet.</p>
</x-card>

<x-card title="Admin" shadow="shadow-lg" padding="p-10">
    <p>Större kort.</p>
</x-card>
```

---

## Slots

Det finns två viktiga slot-varianter:

```text
{{ slot }}
{{ $slot }}
```

### `{{ slot }}`

Renderad slot, avsedd för HTML/komponenter.

Används ofta i wrapper-komponenter:

```html
<div class="card">
    {{ slot }}
</div>
```

### `{{ $slot }}`

Slot som variabel/data och escapas.

Använd detta när slotten ska behandlas som text.

---

## Namngivna slots

För komponenter med flera innehållsområden kan namngivna slots användas.

Exempel:

```html
<x-modal>
    <x-slot:title>Bekräfta</x-slot:title>

    Är du säker på att du vill radera?
</x-modal>
```

Komponenten kan sedan rendera slotten enligt template-systemets slot-stöd.

---

## Globala variabler

Templates kan få tillgång till data som delas globalt via viewer/bootstrap/middleware.

Exempel på sådant som ofta delas:

```text
session
currentUser
appName
pageId
csrf token
flash messages
```

Exempel i template:

```html
{{ $currentUser->getAttribute('first_name') }}
```

Exakt vilka globala variabler som finns beror på appens bootstrapping och middleware.

---

## Route helpers i templates

Använd namngivna routes i templates:

```html
<a href="{{ route('home.index') }}">Hem</a>
```

Med parametrar:

```html
<a href="{{ route('users.show', ['id' => $user->id]) }}">
    Visa användare
</a>
```

Se mer i:

- [`ROUTING.md`](ROUTING.md)

---

## Assets i templates

Använd `versioned_file()` för publika assets där det passar.

Exempel:

```html
<link rel="stylesheet" href="{{ versioned_file('/assets/css/app.css') }}">

<script src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Bild:

```html
<img src="{{ versioned_file('/assets/images/graphics/avatar.png') }}" alt="Avatar">
```

Med fallback:

```html
<img
    src="{{ versioned_file($currentUser->getAttribute('avatar'), '/assets/images/graphics/avatar.png') }}"
    alt="Avatar"
>
```

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`IMAGES.md`](IMAGES.md)

---

## CSRF i formulär

För POST-formulär ska du normalt inkludera CSRF-token.

Exempel:

```html
<form method="post" action="{{ route('contact.create') }}">
    {{ csrf_field()|raw }}

    <button type="submit">Skicka</button>
</form>
```

I layout kan CSRF-token även finnas som meta:

```html
<meta name="csrf-token" content="{{ secure_output($session->csrf()) }}">
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`MIDDLEWARE.md`](MIDDLEWARE.md)

---

## Flash messages

Flash-komponenter kan inkluderas i layout:

```html
{% include "components/flash.ratio.php" %}
```

Controllers kan sätta flash-data i session eller via appens hjälpare.

Exakt API beror på appens helpers/scaffolds.

---

## Formulär och old input

Vid valideringsfel kan appen spara old input i session och visa tillbaka det i formuläret.

Exempel i controller:

```php
$this->request->session()->set('old', [
    'email' => $email,
]);
```

I template används appens helpers eller session-data beroende på implementation.

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## Alpine.js i templates

Radix App kan använda Alpine.js i templates.

Exempel:

```html
<div x-data="{ open: false }">
    <button type="button" x-on:click="open = !open">
        Växla
    </button>

    <div x-show="open">
        Innehåll
    </div>
</div>
```

Om appen använder CSP kan inline-script och Alpine behöva hanteras med nonce eller CSP-kompatibla mönster.

Se mer i:

- [`FRONTEND.md`](FRONTEND.md)
- [`SECURITY.md`](SECURITY.md)

---

## CSP nonce

Om appen använder Content Security Policy kan scripts behöva nonce.

Exempel:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Email templates

Email-vyer kan ligga under:

```text
views/emails/
```

Exempel:

```text
views/emails/contact.ratio.php
views/emails/activate.ratio.php
views/emails/password-reset.ratio.php
```

De kan använda en email-layout:

```text
views/layouts/email.ratio.php
```

Se mer i:

- [`MAIL.md`](MAIL.md)

---

## Error views

Felvyer kan ligga under:

```text
views/errors/
```

Exempel:

```text
views/errors/403.php
views/errors/404.php
views/errors/413.php
views/errors/419.php
views/errors/500.php
views/errors/503.php
```

Vissa error views kan vara vanlig PHP i stället för `.ratio.php`, beroende på hur error handlern renderar fel.

---

## Skapa en view

Via CLI:

```bash
php radix make:view pages/home
```

Det skapar normalt en `.ratio.php`-fil under:

```text
views/
```

Kör hjälp:

```bash
php radix make:view --help
```

---

## Templates från scaffolds

Scaffolds kan lägga till:

- layouts
- pages
- components
- auth views
- admin views
- user views
- email views
- error views

Exempel:

```bash
php radix scaffold:install auth --force-placeholders
```

Efter scaffold-installation kan du behöva rensa cache:

```bash
php radix cache:clear
```

Se mer i:

- [`CLI.md`](CLI.md)

---

## Template-cache

Templates kompileras och cachas för prestanda.

Cache path styrs normalt via `.env`, till exempel:

```text
VIEWS_CACHE_PATH=cache/views
```

Om du ändrar templates och inte ser ändringarna:

```bash
php radix cache:clear
```

Du kan också behöva rensa OPcache/webserver-cache beroende på miljö.

Se mer i:

- [`CACHE.md`](CACHE.md)

---

## Säkerhet

### Auto-escaping

Använd vanlig output för användarinput:

```html
{{ $userInput }}
```

### Rå output

Använd bara `|raw` för säkert innehåll:

```html
{{ $trustedHtml|raw }}
```

### URLs

Escapa URL:er och använd helpers:

```html
<a href="{{ route('home.index') }}">Hem</a>
```

### Forms

Använd CSRF-token i state-changing formulär:

```html
{{ csrf_field()|raw }}
```

### Uploads

Visa inte uppladdat innehåll som rå HTML.

---

## Bra praxis

- använd layouts för gemensam struktur
- använd components för återanvändbara UI-delar
- använd named routes i stället för hårdkodade URL:er
- använd `versioned_file()` för assets
- använd `{{ ... }}` för användarinput
- använd `|raw` sparsamt
- inkludera CSRF-token i POST-formulär
- rensa cache vid template-problem
- håll email templates enkla
- håll komponenter små och tydliga

---

## Felsökning

### Ändring syns inte

Rensa cache:

```bash
php radix cache:clear
```

### View hittas inte

Kontrollera att view-namnet matchar filen.

```php
return $this->view('home.index');
```

ska normalt motsvara:

```text
views/home/index.ratio.php
```

### Layout hittas inte

Kontrollera sökvägen i:

```html
{% extends "layouts/app.ratio.php" %}
```

### Block renderas inte

Kontrollera att layouten har matchande:

```html
{% yield body %}
```

och att vyn definierar:

```html
{% block body %}
    ...
{% endblock %}
```

### Variabel saknas

Kontrollera att controllern skickar med variabeln:

```php
return $this->view('home.index', [
    'name' => $name,
]);
```

eller att variabeln delas globalt.

### Komponent hittas inte

Kontrollera att komponenten ligger under:

```text
views/components/
```

och att namnet matchar `<x-...>`.

### Slot renderas escapad

Om du bygger wrapper-komponent och vill rendera HTML i slotten, använd:

```html
{{ slot }}
```

inte:

```html
{{ $slot }}
```

### CSRF-fel vid formulär

Kontrollera att formuläret innehåller:

```html
{{ csrf_field()|raw }}
```

och att session fungerar.

---

## Relaterat

- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`ROUTING.md`](ROUTING.md)
- [`FRONTEND.md`](FRONTEND.md)
- [`IMAGES.md`](IMAGES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)
- [`CACHE.md`](CACHE.md)
- [`MAIL.md`](MAIL.md)
