# docs/TEMPLATES.md

← [`Tillbaka till index`](INDEX.md)

# Templates (Radix App)

Radix App använder ett eget templatesystem (via `RadixTemplateViewer`) som kombinerar PHP med en renare syntax för layouts och komponenter.

Mallfiler använder filändelsen:

- `.ratio.php`

---

## Grundläggande syntax

### Variabler (auto-escaping)

Skriv ut en variabel (auto-escapas för säkerhet):

```html
{{ $name }}
```

### Rå HTML (`|raw`) — använd med försiktighet

Om du vill skriva ut HTML utan escaping:

```html
{{ $html_content | raw }}
```

> Använd bara `|raw` när du vet att innehållet är säkert (t.ex. genererat av dig själv eller sanerat). Annars riskerar du XSS.

---

## PHP-direktiv (`{% ... %}`)

Du kan köra PHP-kontrollstrukturer inuti `{% %}`:

```html
{% foreach ($items as $item): %}
    <li>{{ $item }}</li>
{% endforeach; %}
```

---

## Layouts och arv

Du kan definiera en baslayout och låta vyer ärva från den.

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

---

## Komponenter (`<x-...>`)

Komponenter ligger i:

- `views/components/`

De anropas med `<x-...>`-syntax.

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

---

## Props (`{% props(...) %}`) i komponenter

Komponenter kan deklarera vilka “props” de förväntar sig (och ev. standardvärden).  
Det gör komponenter mer robusta och själv-dokumenterande.

### Exempel: props med default-värden

**Komponent (`views/components/card.ratio.php`):**
```php
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

**Användning:**
```html
<x-card title="Profil">
    <p>Innehåll i kortet.</p>
</x-card>

<x-card title="Admin" shadow="shadow-lg" padding="p-10">
    <p>Större kort.</p>
</x-card>
```

### Slot-konvention (viktigt)

- `{{ slot }}` = renderad slot (tänkt för HTML från nästlade komponenter)
- `{{ $slot }}` = slot som data (escapas)

Om du bygger wrapper-komponenter som ska kunna innehålla HTML/komponenter inuti är `{{ slot }}` oftast rätt val.

---

## Namngivna slots

Om en komponent behöver flera innehållsområden kan du använda namngivna slots:

```html
<x-modal>
    <x-slot:title>Bekräfta</x-slot:title>
    Är du säker på att du vill radera?
</x-modal>
```

---

## Inkluderingar

Du kan inkludera mindre fragment:

```html
{% include "partials/nav.ratio.php" %}
```

---

## Globala variabler

Du kan dela data med alla vyer via viewer (t.ex. genom att registrera “shared” data vid boot).

I template:

```html
{{ $appName }}
```

---

## Cache

Templates kompileras och cachas för prestanda.

Vid felsökning (eller om du har ändrat templates och inte ser effekten direkt), rensa cache:

```bash
php radix cache:clear
``
