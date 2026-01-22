# Radix Framework Documentation

Välkommen till dokumentationen för Radix – ett modernt, lättviktigt PHP-ramverk byggt för snabb utveckling med full kontroll.

## Snabbstart
1. **Installation**: Följ stegen i [README.md](../README.md) för att sätta upp miljön.
2. **Konfiguration**: Se [CONFIG.md](CONFIG.md) för hur du ställer inte `.env` och filer i `config/`.
3. **CLI**: Lär dig kommandona i [CLI.md](CLI.md) för att hantera cachen och databasen.

## Kärnkoncept
- **[Routing](ROUTING.md)**: Hur du definierar webb- och API-rutter.
- **[Controllers](CONTROLLERS.md)**: Hantera logik och returnera svar (Web & API).
- **[Middleware](MIDDLEWARE.md)**: Hantera request/response-pipelinen (auth, loggning, etc).
- **[Validation](VALIDATION.md)**: Validera användardata och filer med enkla regler.
- **[File Handling](FILES.md)**: Läs och skriv JSON, CSV och XML med Reader & Writer.
- **[Images & Uploads](IMAGES.md)**: Hantera bilduppladdning och bildbehandling.
- **[Geo Location](GEOLOCATION.md)**: Identifiera besökarens geografiska position via IP.
- **[Templates](TEMPLATES.md)**: Bygg snygga vyer med layouts och komponenter.
- **[Frontend & Components](FRONTEND.md)**: Arbeta med komponenter, props, slots, Tailwind 4 och Alpine.js.
- **[Services & DI](SERVICES.md)**: Förstå hur beroenden injiceras och tjänster registreras.
- **[Frontend](FRONTEND.md)**: Arbeta med Tailwind 4 och Alpine.js i vyer.

## Databas & Modellering
- **[Database & Migrations](DATABASE.md)**: Skapa tabeller och populera dem med data.
- **[ORM](ORM.md)**: Arbeta med modeller och databasen på ett objektorienterat sätt.

## Arkitektur & Logik
- **[Architecture](ARCHITECTURE.md)**: Övergripande systemdesign och request-cykeln.
- **[Events & Listeners](EVENTS.md)**: Koppla isär din logik med händelser.
- **[API Development](API.md)**: Bygg RESTful API:er med JSON-svar.
- **[Caching](CACHE.md)**: Spara temporär data med FileCache.
- **[Logging](LOGGING.md)**: Logga händelser och fel till disk.
- **[Email Handling](MAIL.md)**: Skicka mejl via SMTP och mallar.
- **[HTTP System](HTTP.md)**: Djupdykning i Request, Response och Redirects.
- **[Security](SECURITY.md)**: CSP, CSRF-skydd och säker utdata.

## Arbetsflöde & Driftsättning
- **[Cookbook](COOKBOOK.md)**: Praktiska guider för vanliga utvecklingsuppgifter.
- **[Development Flow](DEVELOPMENT_FLOW.md)**: Hur vi använder TDD, PHPStan och kodstil.
- **[Releases](RELEASES.md)**: Checklista för deployment och produktion.

## Utveckling & Bidrag
- **[Testing](TESTING.md)**: Hur du skriver och kör tester med PHPUnit.
- **[CI Setup](../github-settings/CI_TEMPLATE_SETUP.md)**: Checklista för att sätta upp GitHub Actions.
- **[CI Variables](../github-settings/CI_VARIABLES.md)**: Beskrivning av miljövariabler för CI.
- **[Contributing](CLI_CONTRIBUTING.md)**: Guide för att bygga ut CLI-verktyget.
```