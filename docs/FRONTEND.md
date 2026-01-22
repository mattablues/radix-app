# Frontend & Templates (FRONTEND.md)

Radix använder en modern frontend-stack med **Tailwind CSS v4**, **Alpine.js** och den inbyggda mallmotorn **RadixTemplateViewer** (`.ratio.php`).

## 1. Komponenter (<x-komponent>)

Komponenter lagras i `views/components/`. Du anropar dem med syntaxen `<x-namn>`. Om en komponent ligger i en undermapp, t.ex. `views/components/ui/card.ratio.php`, anropar du den med `<x-ui.card>`.

### 1.1 Props (@props)
Varje komponent kan deklarera vilka variabler den förväntar sig och deras standardvärden med `@props`. Detta gör komponenten mer robust och själv-dokumenterande.

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

## 2. Slots (Innehållsplatshållare)

Slots används för att skicka in HTML-innehåll i en komponent.

### 2.1 Standard Slot ($slot)
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

### 2.2 Namngivna Slots (<x-slot:namn>)
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

## 3. Alpine.js Integration

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

## 4. Tillgång till global data

Variabler som registrerats via `$viewer->shared('key', 'value')` är tillgängliga i alla komponenter och vyer. Du kan också använda `$global`-prefixet för att vara extra tydlig:

```html
<p>Inloggad som: {{ $globalUser->name }}</p>
```

---

## 5. Versionshantering av assets

För att undvika cache-problem i webbläsaren vid deployment, använd hjälparen `versioned_file()`. Den lägger till en tidshash baserat på filens senaste ändring.

```html
<link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
<script src="{{ versioned_file('/js/app.js') }}"></script>
```