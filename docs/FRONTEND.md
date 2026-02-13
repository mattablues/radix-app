# Frontend & Templates (FRONTEND.md)

Radix använder en modern frontend-stack med **Tailwind CSS v4**, **Alpine.js** och den inbyggda mallmotorn **RadixTemplateViewer** (`.ratio.php`).

## 1. Komponenter (<x-komponent>)

Komponenter lagras i `views/components/`. Du anropar dem med syntaxen `<x-namn>`. Om en komponent ligger i en undermapp, t.ex. `views/components/ui/card.ratio.php`, anropar du den med `<x-ui.card>`.

### 1.1 Props ({% props %})
Varje komponent kan deklarera vilka variabler den förväntar sig och deras standardvärden med `{% props(...) %}`. Detta gör komponenten mer robust och själv-dokumenterande.

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

## 2. Auto-escaping & `|raw` (VIKTIGT)

RadixTemplateViewer auto-escapar som default allt som renderas via `{{ ... }}` med `secure_output(...)`.

### 2.1 Grundregel: `{{ $var }}` är text (escapas)
Allt som skrivs med `{{ $var }}` behandlas som **text** och HTML-escapas.

```php
<h4>{{ $title }}</h4>
```

Om `$title` råkar innehålla HTML (t.ex. `<b>Hej</b>`) renderas det som text: `&lt;b&gt;Hej&lt;/b&gt;`.

### 2.2 Undantag: `|raw` (endast när du vet att det är säkert)
`|raw` säger uttryckligen “rendera utan escaping”.

Använd detta **mycket sparsamt** och bara när innehållet är:
- genererat av er själva, eller
- sanerat/whitelistat innan rendering.

```php
<div>{{ $htmlFragment|raw }}</div>
```

**Rekommendation:** Om värdet kan innehålla användargenererat innehåll → använd inte `|raw`.

### 2.3 Slot-konvention: `{{ slot }}` vs `{{ $slot }}`
I templates finns två vanliga sätt att skriva ut slot:

- `{{ slot }}` = **renderad slot (raw)**  
  Använd när slotten kan innehålla HTML från nästlade komponenter och ska renderas som HTML.

- `{{ $slot }}` = **slot som data (escapas)**  
  Använd när du vill visa slot-innehållet som text (t.ex. felsökning eller “visa markupen”).

**Exempel (wrapper som ska stödja nested components):**
```php
<div class="wrapper">{{ slot }}</div>
```

**Exempel (visa slot som text):**
```php
<pre>{{ $slot }}</pre>
```

### 2.4 Props som innehåller HTML
Skicka helst **data** som props och bygg markup i komponenten.

Om en prop verkligen ska kunna innehålla HTML: gör det till ett **medvetet API** och använd `|raw` i komponenten:

```php
{% props(['titleHtml' => '']) %}
<h4>{{ $titleHtml|raw }}</h4>
```

Det gör att valet att rendera rå HTML ligger i komponenten (inte i anropsstället), vilket är lättare att granska.

---

## 3. Slots (Innehållsplatshållare)

Slots används för att skicka in HTML-innehåll i en komponent.

### 3.1 Standard Slot ($slot)
Allt innehåll som placeras mellan komponentens start- och slut-tagg hamnar i variabeln `$slot`.

**Exempel: `views/components/card.ratio.php`**
```php
<div class="card p-6 bg-white shadow">
    {{ $slot }}
</div>
```

**Användning:**
```html
<x-card>
    <p>Det här innehållet hamnar i $slot.</p>
</x-card>
```

### 3.2 Namngivna Slots (<x-slot:namn>)
Om en komponent behöver innehåll på flera olika ställen (t.ex. header, body, footer) används namngivna slots.

**Exempel: `views/components/modal.ratio.php`**
```php
{% props(['class' => '']) %}

<div class="modal {{ $class }}">
    <header class="border-b">
        {{ $header }}
    </header>

    <main class="p-4">
        {{ $slot }} <!-- Standardinnehåll -->
    </main>

    <footer class="border-t">
        {{ $footer }}
    </footer>
</div>
```

**Användning:**
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

## 4. Alpine.js Integration

Radix är optimerat för Alpine.js. Du kan använda Alpine-direktiv direkt i dina komponenter och slots.

```html
<x-card x-data="{ open: false }">
    <button @click="open = !open">Visa mer</button>

    <div x-show="open" x-collapse>
        {{ $slot }}
    </div>
</x-card>
```

---

## 5. Tillgång till global data

Variabler som registrerats via `$viewer->shared('key', 'value')` är tillgängliga i alla komponenter och vyer.

```html
<p>Inloggad som: {{ $globalUser->name }}</p>
```

---

## 6. Versionshantering av assets

För att undvika cache-problem i webbläsaren vid deployment, använd hjälparen `versioned_file()`. Den lägger till en tidshash baserat på filens senaste ändring.

```html
<link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
<script src="{{ versioned_file('/js/app.js') }}"></script>
```
