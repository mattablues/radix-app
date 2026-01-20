# Radix Template System

Radix använder ett eget templatesystem (via `RadixTemplateViewer`) som kombinerar kraften i PHP med en renare syntax för layouts och komponenter. Mallfiler använder filändelsen `.ratio.php`.

## Grundläggande syntax

### Variabler
För att skriva ut en variabel (som automatiskt escapas för säkerhet):
```html
{{ $name }}
```

Om du vill skriva ut rå HTML (använd med försiktighet):
```html
{{ $html_content | raw }}
```

### PHP-direktiv
Du kan köra vanliga PHP-kommandon inuti `{% %}`:
```html
{% foreach ($items as $item): %}
    <li>{{ $item }}</li>
{% endforeach; %}
```

## Layouts och Arv

Du kan definiera en baslayout och sedan utöka den i dina vyer.

**Layout (`views/layouts/app.ratio.php`):**
```html
<!DOCTYPE html>
<html>
<head>
    <title>{% yield title %}Standardtitel{% endyield title %}</title>
</head>
<body>
    <main>
        {% yield content %}
    </main>
</body>
</html>
```

**Vy (`views/home.ratio.php`):**
```html
{% extends "layouts/app.ratio.php" %}

{% block title %}Hem - Min App{% endblock %}

{% block content %}
    <h1>Välkommen hem!</h1>
{% endblock %}
```

## Komponenter

Komponenter ligger i `views/components/` och anropas med `<x-` taggar.

**Exempelkomponent (`views/components/alert.ratio.php`):**
```html
<div class="alert alert-{{ $type }}">
    {{ $slot }}
</div>
```

**Användning:**
```html
<x-alert type="danger">
    Något gick fel!
</x-alert>
```

### Namngivna Slots
Om en komponent behöver mer än ett innehållsområde:
```html
<x-modal>
    <x-slot:title>Bekräfta</x-slot:title>
    Är du säker på att du vill radera?
</x-modal>
```

## Inkluderingar
Du kan inkludera mindre fragment (t.ex. en header) direkt:
```html
{% include "partials/nav.ratio.php" %}
```

## Globala Variabler
Du kan dela data med alla vyer via containern eller direkt i `RadixTemplateViewer`:
```php
$viewer->shared('appName', 'Radix Framework');
```

I mallen:
```html
{{ $appName }}
```

## Caching
Mallar kompileras och cachas i `cache/views/` för maximal prestanda.
- I **development** (`APP_ENV=dev`) är cachen inaktiverad.
- I **production** minifieras koden automatiskt vid kompilering.

Du kan rensa cachen via CLI:
```bash
php radix cache:clear
```