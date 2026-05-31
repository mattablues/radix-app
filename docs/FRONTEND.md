# docs/FRONTEND.md

← [`Tillbaka till index`](INDEX.md)

# Frontend (Radix App)

Radix App använder en frontend-stack med:

- Tailwind CSS v4
- Alpine.js
- esbuild
- Radix templates (`.ratio.php`)
- publika assets under `public/assets/`

Den här guiden fokuserar på frontend-arbetsflödet i appen.

För ren template-syntax, se:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Översikt

Frontend-källor ligger i:

```text
resources/
  js/
  tailwind/
```

Byggda publika filer hamnar i:

```text
public/assets/
  css/
    app.css
  js/
    app.js
  images/
    graphics/
  favicons/
```

---

## Installera frontend dependencies

Efter installation av appen:

```bash
npm install
```

Radix App använder npm som package manager.

---

## NPM scripts

Vanliga scripts:

```bash
npm run start:dev
npm run start:build
npm run build:css
npm run build:js
npm run watch:tailwind
npm run watch:alpinejs
```

### Development watch

Kör Tailwind och JavaScript i watch-läge:

```bash
npm run start:dev
```

Det kör normalt:

```text
watch:tailwind
watch:alpinejs
```

parallellt.

### Production build

Bygg CSS och JS:

```bash
npm run start:build
```

Det kör normalt:

```text
build:js
build:css
```

---

## CSS build

Tailwind entrypoint:

```text
resources/tailwind/index.css
```

Output:

```text
public/assets/css/app.css
```

Kör watch:

```bash
npm run watch:tailwind
```

Bygg optimerad CSS:

```bash
npm run build:css
```

---

## JavaScript build

JavaScript entrypoint:

```text
resources/js/index.js
```

Output:

```text
public/assets/js/app.js
```

Kör watch:

```bash
npm run watch:alpinejs
```

Bygg minifierad JS:

```bash
npm run build:js
```

---

## Frontend-struktur

Exempel på JavaScript-filer:

```text
resources/js/
  index.js
  ui-init.js
  addTableAria.js
  search.js
  search-init.js
  search-table.js
  search-users.js
  search-deleted-users.js
  search-blocked-emails.js
  search-system-events.js
  search-system-updates.js
  search-profiles.js
```

Exempel på Tailwind/CSS-filer:

```text
resources/tailwind/
  index.css
  base.css
  components.css
  theme.css
  utilities.css
```

---

## Publika assets

Publika app-assets ligger under:

```text
public/assets/
```

Rekommenderad struktur:

```text
public/assets/
  css/
  js/
  images/
    graphics/
  favicons/
```

Exempel:

```text
public/assets/css/app.css
public/assets/js/app.js
public/assets/images/graphics/avatar.png
public/assets/favicons/favicon.svg
```

---

## Assets vs uploads

Det är viktigt att skilja på:

```text
public/assets  = appens betrodda frontend-assets
public/uploads = användargenererade filer
```

Appens egna CSS, JS, logotyper, ikoner och grafik ska ligga i:

```text
public/assets/
```

Användaruppladdningar ska ligga i:

```text
public/uploads/
```

Se mer i:

- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Länka CSS i layout

I en layout kan du länka CSS så här:

```html
<link rel="stylesheet" href="{{ versioned_file('/assets/css/app.css') }}">
```

`versioned_file()` hjälper till att undvika cache-problem genom att lägga till versionsinformation baserat på filens senaste ändring.

---

## Länka JavaScript i layout

Exempel:

```html
<script src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Om CSP kräver nonce:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`TEMPLATES.md`](TEMPLATES.md)

---

## Favicons

Favicons ligger normalt i:

```text
public/assets/favicons/
```

Exempel i layout:

```html
<link rel="icon" type="image/png" href="/assets/favicons/favicon-96x96.png" sizes="96x96">
<link rel="icon" type="image/svg+xml" href="/assets/favicons/favicon.svg">
<link rel="shortcut icon" href="/assets/favicons/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/favicons/apple-touch-icon.png">
<link rel="manifest" href="/assets/favicons/site.webmanifest">
```

---

## Bilder och grafik

Appens egna statiska bilder ligger normalt i:

```text
public/assets/images/
```

Exempel:

```text
public/assets/images/graphics/avatar.png
```

Använd i template:

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

- [`IMAGES.md`](IMAGES.md)

---

## Tailwind CSS

Radix App använder Tailwind CSS v4 via Tailwind CLI.

Entry file:

```text
resources/tailwind/index.css
```

Den kan importera eller samla:

```text
base.css
components.css
theme.css
utilities.css
```

Bygg:

```bash
npm run build:css
```

Watch:

```bash
npm run watch:tailwind
```

---

## CSS-organisation

Rekommenderad uppdelning:

```text
base.css        reset/base-regler och elementstandarder
theme.css       färger, tokens, variabler
components.css  återanvändbara komponentklasser
utilities.css   app-specifika utilities
index.css       entrypoint
```

Håll gärna återanvändbara klasser i `components.css` och appens design tokens i `theme.css`.

---

## Alpine.js

Alpine används för interaktivitet direkt i templates.

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

Kort syntax:

```html
<button @click="open = !open">
    Växla
</button>
```

---

## Alpine plugins

Appen kan använda Alpine-relaterade paket som:

```text
@alpinejs/collapse
@alpinejs/csp
@alpinejs/focus
@alpinejs/ui
@imacrayon/alpine-ajax
```

Exakt vilka som initieras styrs av appens JavaScript entrypoint.

---

## Alpine och CSP

Om appen använder strikt Content Security Policy kan Alpine behöva CSP-vänliga mönster.

Rekommendationer:

- använd bundlad JS via `resources/js/index.js`
- använd `nonce` på script-taggar om CSP kräver det
- undvik onödigt inline-script
- använd `@alpinejs/csp` om appens policy kräver det

Exempel:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Radix components

Templates kan använda komponenter från:

```text
views/components/
```

Exempel:

```html
<x-card>
    <p>Innehåll i kortet.</p>
</x-card>
```

Komponent i undermapp:

```text
views/components/ui/card.ratio.php
```

kan normalt anropas som:

```html
<x-ui.card>
    <p>Innehåll</p>
</x-ui.card>
```

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Props i komponenter

Komponenter kan deklarera props:

```html
{% props([
    'type' => 'button',
    'class' => 'btn-primary',
    'label'
]) %}

<button type="{{ $type }}" class="px-4 py-2 rounded {{ $class }}">
    {{ $label }}
</button>
```

Användning:

```html
<x-ui.button label="Spara" />

<x-ui.button label="Radera" class="bg-red-600" type="submit" />
```

---

## Slots

Standard slot:

```html
<x-card>
    <p>Det här blir slot-innehåll.</p>
</x-card>
```

I komponenten:

```html
<div class="card">
    {{ slot }}
</div>
```

Slot-konvention:

```text
{{ slot }}  = renderad slot, för HTML/komponenter
{{ $slot }} = slot som data/text och escapas
```

---

## Namngivna slots

Exempel:

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

Se mer i:

- [`TEMPLATES.md`](TEMPLATES.md)

---

## Auto-escaping och `|raw`

Radix templates auto-escapar vanlig output:

```html
{{ $title }}
```

Om `$title` innehåller HTML renderas det som text.

Rå output:

```html
{{ $htmlFragment|raw }}
```

Använd `|raw` bara när innehållet är säkert.

Vanliga säkra exempel:

```html
{{ csrf_field()|raw }}
```

Osäkert exempel:

```html
{{ $userInput|raw }}
```

---

## Forms

För POST-formulär:

```html
<form method="post" action="{{ route('contact.create') }}">
    {{ csrf_field()|raw }}

    <label for="email">E-post</label>
    <input id="email" name="email" type="email">

    <button type="submit">Skicka</button>
</form>
```

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)
- [`SECURITY.md`](SECURITY.md)

---

## Search och interaktivitet

Radix App kan ha JavaScript-moduler för sök och UI-initiering.

Exempel på filer:

```text
resources/js/search.js
resources/js/search-init.js
resources/js/search-table.js
resources/js/search-users.js
resources/js/search-profiles.js
```

Rekommendation:

- håll generell söklogik i återanvändbara moduler
- håll endpoint-specifik logik i separata filer
- använd data-attribut i HTML för att koppla endpoint/target

Exempel:

```html
<input
    id="search-profiles"
    data-search-endpoint="{{ route('api.search.profiles') }}"
    placeholder="Sök användare..."
>
```

---

## Accessibility

Tänk på:

- använd `label` för formulärfält
- använd `aria-label` där text saknas
- använd semantiska element
- se till att modaler hanterar fokus
- använd tydliga knapptexter
- lita inte enbart på färg för status

Exempel:

```html
<label for="email">E-post</label>
<input id="email" name="email" type="email">
```

Knapp med ikon:

```html
<button type="button" aria-label="Öppna meny">
    <!-- ikon -->
</button>
```

---

## Responsive design

Tailwind används för responsiva varianter.

Exempel:

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    ...
</div>
```

Vanliga prefixes:

```text
sm:
md:
lg:
xl:
2xl:
```

---

## Cache busting

Använd `versioned_file()` för CSS/JS/bilder:

```html
<link rel="stylesheet" href="{{ versioned_file('/assets/css/app.css') }}">
<script src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

Det hjälper webbläsaren att hämta ny version efter deploy.

---

## Template cache

Om templateändringar inte syns:

```bash
php radix cache:clear
```

Om CSS/JS-ändringar inte syns:

```bash
npm run start:build
php radix cache:clear
```

Kontrollera även webbläsarens cache.

---

## Build inför deploy

Inför deploy:

```bash
npm install
npm run start:build
composer install --no-dev --optimize-autoloader
php radix cache:clear
```

Exakt deployflöde beror på miljö.

---

## Doctoc

Projektet har ett npm-script för README TOC:

```bash
npm run toc
```

Det kör doctoc mot README.

---

## Bra praxis

- använd `resources/` för källfiler
- använd `public/assets/` för byggda publika filer
- använd `public/uploads/` för användargenererade filer
- bygg CSS/JS inför deploy
- använd `versioned_file()` i templates
- använd `{{ ... }}` för user content
- använd `|raw` sparsamt
- håll Alpine-logik enkel i templates
- flytta större JS till `resources/js/`
- tänk på CSP om inline-interaktivitet används
- skriv tillgänglig HTML

---

## Felsökning

### CSS ändras inte

Kör:

```bash
npm run build:css
```

eller i watch-läge:

```bash
npm run watch:tailwind
```

Rensa cache:

```bash
php radix cache:clear
```

Kontrollera att layouten länkar:

```html
<link rel="stylesheet" href="{{ versioned_file('/assets/css/app.css') }}">
```

### JavaScript ändras inte

Kör:

```bash
npm run build:js
```

eller:

```bash
npm run watch:alpinejs
```

Kontrollera:

```html
<script src="{{ versioned_file('/assets/js/app.js') }}"></script>
```

### Alpine fungerar inte

Kontrollera:

- att `app.js` laddas
- att Alpine startas i `resources/js/index.js`
- att CSP inte blockerar script
- browser console

### Tailwind-klasser saknas

Kontrollera:

- att Tailwind build körts
- att `resources/tailwind/index.css` är entrypoint
- att rätt filer scannas av Tailwind v4-setup
- att klassen inte skapas dynamiskt på ett sätt Tailwind inte hittar

### Asset 404

Kontrollera att filen finns under:

```text
public/assets/
```

och att URL:en börjar med:

```text
/assets/
```

### Template ändras inte

Kör:

```bash
php radix cache:clear
```

---

## Relaterat

- [`TEMPLATES.md`](TEMPLATES.md)
- [`IMAGES.md`](IMAGES.md)
- [`FILES.md`](FILES.md)
- [`SECURITY.md`](SECURITY.md)
- [`CACHE.md`](CACHE.md)
- [`ROUTING.md`](ROUTING.md)
