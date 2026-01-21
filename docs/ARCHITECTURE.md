# Arkitekturöversikt (ARCHITECTURE.md)

Detta dokument beskriver de tekniska principerna bakom Radix-systemet och hur ett anrop (Request) bearbetas.

## 1. Request-cykeln
1.  **Entry Point**: Alla anrop börjar i `public/index.php`.
2.  **Bootstrap**: `bootstrap/app.php` initierar containern och laddar konfiguration.
3.  **Middleware**: Anropet passerar genom globala och rutt-specifika middlewares (t.ex. `RateLimiter`, `RequestLogger`).
4.  **Routing**: `routes/` mappar URL:en till en metod i en `Controller`.
5.  **Controller**: Kontrollern i `src/Controllers` hanterar logik, interagerar med modeller och returnerar en `Response`.
6.  **Template Engine**: Vyer renderas via `RadixTemplateViewer`.

## 2. Template Engine (RadixTemplateViewer)
Radix använder en egen motor för `.ratio.php`-filer. Den stöder:
- **Inheritance**: `{% extends "layout.ratio.php" %}` och `{% block %}`.
- **Components**: `<x-komponent attribut="värde">` som mappar mot `views/components/`.
- **Caching**: Kompilerade mallar sparas i `cache/views/` för maximal prestanda.
- **Säkerhet**: Automatisk XSS-skydd via `secure_output()` i alla `{{ }}`-block.

---

# Databas & Modeller (DATABASE.md)

Radix använder ett Active Record-inspirerat mönster för databashantering.

## 1. Modeller (src/Models)
Modeller ärver från ett baslager i frameworket. De hanterar:
- **Fillable fields**: Definierar vilka fält som får mass-assignas.
- **Relations**: Metoder som `user()` i `Status`-modellen definierar kopplingar mellan tabeller.

## 2. SystemEvent & Loggning
Använd `App\Models\SystemEvent::log()` för att spara viktiga händelser i databasen:
- `info`: Allmänna händelser (t.ex. "Konto aktiverat").
- `warning`: Potentiella problem.
- `error`: Kritiska fel.

## 3. Tokens & Säkerhet
- **Personal Access Tokens**: Skapas via `Token::createToken($userId, $name)`.
- **Activation Tokens**: Används vid registrering och lagras som HMAC-hashar i `status`-tabellen.

---

# Frontend-guide (FRONTEND.md)

Projektet använder en modern frontend-stack baserad på Tailwind CSS och Alpine.js.

## 1. Tailwind CSS 4
Vi använder Tailwind 4 CLI (`@tailwindcss/cli`). 
- **Konfiguration**: Sker främst via CSS-variabler i huvudfilen under `resources/css/`.
- **Build**: Körs via `npm run start:build` (lokalt) eller automatiskt i CI om `ENABLE_FRONTEND_BUILD=1`.

## 2. Alpine.js
Vi använder Alpine.js 3.14 med flera kraftfulla plugins:
- **@alpinejs/focus**: För tillgängliga modaler och formulär.
- **@alpinejs/collapse**: För dragspelsmenyer.
- **@imacrayon/alpine-ajax**: För sömlösa siduppdateringar utan omladdning.

## 3. Formular & Honeypot
För att skydda mot spam använder vi en dynamisk Honeypot-lösning:
1.  Kontrollern genererar ett `honeypot_id` och sparar i sessionen.
2.  Vyn renderar ett dolt fält med detta ID.
3.  `Validator` i kontrollern verifierar att fältet är tomt.

Exempel på validering:
```php
$validator = new Validator($data, [
    $expectedHoneypotId => 'honeypot',
]);
```