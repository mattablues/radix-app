# docs/FRONTEND.md

← [`Tillbaka till index`](INDEX.md)

# Frontend (Radix App)

Radix App använder en modern frontend-stack med:

- **Tailwind CSS v4**
- **Alpine.js**
- templates i **RadixTemplateViewer** (`.ratio.php`)

> Den här filen fokuserar på hur du jobbar med frontend i views/templates. För ren template-syntax (extends/slots/props osv), se även `docs/TEMPLATES.md`.

---

## 1) Komponenter (`<x-komponent>`)

Komponenter lagras i:

- `views/components/`

Du anropar dem med syntaxen `<x-namn>`.  
Om en komponent ligger i en undermapp, t.ex. `views/components/ui/card.ratio.php`, anropar du den som:

```html
<x-ui.card />
```

---

## 2) Props i komponenter (`{% props %}`)

Komponenter kan deklarera vilka “props” de förväntar sig och deras standardvärden med `{% props(...) %}`.

**Exempel: `views/components/ui/button.ratio.php`**
```php
{% props([
    'type' => 'button',
    'class' => 'btn-primary',
    'label'
]) %}

<button type="{{ $type }}" class="px-4 py-2 rounded {{ $class }}">
    {{ $label }}
</button>
```

**Användning:**
```html
<!-- Använder standardvärden -->
<x-ui.button label="Spara" />

<!-- Skriver över standardvärden -->
<x-ui.button label="Radera" class="bg-red-600" type="submit" />
```

---

## 3) Auto-escaping & `|raw` (VIKTIGT)

RadixTemplateViewer auto-escapar som default allt som renderas via `{{ ... }}`.

### Grundregel: `{{ $var }}` är text (escapas)

```html
<h4>{{ $title }}</h4>
```

Om `$title` råkar innehålla HTML renderas det som text (HTML-escapat).

### Undantag: `|raw` (endast när du vet att det är säkert)

```html
<div>{{ $htmlFragment|raw }}</div>
```

Använd `|raw` sparsamt och bara om innehållet är kontrollerat/sanerad.

### Slot-konvention: `{{ slot }}` vs `{{ $slot }}`

- `{{ slot }}` = renderad slot (raw), för markup/nestlade komponenter
- `{{ $slot }}` = slot som data (escapas), för “visa som text”

**Wrapper-exempel:**
```html
<div class="wrapper">{{ slot }}</div>
```

**Debug-exempel:**
```html
<pre>{{ $slot }}</pre>
```

### Props som innehåller HTML

Skicka helst data som props och bygg markup i komponenten.

Om en prop verkligen ska kunna innehålla HTML: gör det till ett medvetet API och använd `|raw` i komponenten:

```php
{% props(['titleHtml' => '']) %}
<h4>{{ $titleHtml|raw }}</h4>
```

---

## 4) Slots (innehållsplatshållare)

Slots används för att skicka in HTML-innehåll i en komponent.

### Standard slot

Innehåll mellan start/slut-tag hamnar i komponentens slot.

```html
<x-card>
    <p>Det här blir slot-innehåll.</p>
</x-card>
```

### Namngivna slots (`<x-slot:namn>`)

Använd när en komponent har flera “ytor” (header/footer osv).

```html
<x-modal class="max-w-lg">
    <x-slot:header>
        <h3 class="text-xl">Systemmeddelande</h3>
    </x-slot:header>

    <p>Är du säker på att du vill fortsätta?</p>

    <x-slot:footer>
        <button>Avbryt</button>
        <button>OK</button>
    </x-slot:footer>
</x-modal>
```

---

## 5) Alpine.js i templates

Du kan använda Alpine-direktiv direkt i komponenter och views.

```html
<x-card x-data="{ open: false }">
    <button @click="open = !open">Visa mer</button>

    <div x-show="open" x-collapse>
        {{ slot }}
    </div>
</x-card>
```

---

## 6) Global data i views

Variabler som delas med viewer (t.ex. via `$viewer->shared(...)`) är tillgängliga i alla komponenter och vyer.

```html
<p>Inloggad som: {{ $globalUser->name }}</p>
```

---

## 7) Versionshantering av assets

För att undvika cache-problem i webbläsaren vid deployment, använd hjälparen `versioned_file()`.
Den lägger till en tidshash baserat på filens senaste ändring.

```html
<link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
<script src="{{ versioned_file('/js/app.js') }}"></script>
```

### Om du använder CSP med `nonce`

Om din Content Security Policy kräver `nonce` på inline/script-taggar, använd nonce-attributet på script-taggen:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/js/app.js') }}"></script>
```

> Obs: `nonce` behövs bara om du har CSP aktiverat och policyn kräver det för script.
