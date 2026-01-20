# Radix Framework Documentation

Välkommen till dokumentationen för Radix – ett modernt, lättviktigt PHP-ramverk byggt för snabb utveckling med full kontroll.

## Snabbstart
1. **Installation**: Följ stegen i [README.md](../README.md) för att sätta upp miljön.
2. **Konfiguration**: Se [CONFIG.md](CONFIG.md) för hur du ställer in `.env` och filer i `config/`.
3. **CLI**: Lär dig kommandona i [CLI.md](CLI.md) för att hantera cachen och databasen.

## Kärnkoncept
- **[Routing](ROUTING.md)**: Hur du definierar webb- och API-rutter.
- **[Controllers](CONTROLLERS.md)**: Hantera logik och returnera svar (Web & API).
- **[Middleware](MIDDLEWARE.md)**: Hantera request/response-pipelinen (auth, loggning, etc).
- **[Validation](VALIDATION.md)**: Validera användardata och filer med enkla regler.
- **[File Handling](FILES.md)**: Läs och skriv JSON, CSV och XML med Reader & Writer.
- **[Images & Uploads](IMAGES.md)**: Hantera bilduppladdning och bildbehandling.
- **[Templates](TEMPLATES.md)**: Bygg snygga vyer med layouts och komponenter.
- **[Services & DI](SERVICES.md)**: Förstå hur beroenden injiceras och tjänster registreras.

## Databas & Modellering
- **[Database & Migrations](DATABASE.md)**: Skapa tabeller och populera dem med data.
- **[ORM](ORM.md)**: Arbeta med modeller och databasen på ett objektorienterat sätt.

## Arkitektur & Logik
- **[Events & Listeners](EVENTS.md)**: Koppla isär din logik med händelser.
- **[API Development](API.md)**: Bygg RESTful API:er med JSON-svar.
- **[Caching](CACHE.md)**: Spara temporär data med FileCache.
- **[Logging](LOGGING.md)**: Logga händelser och fel till disk.
- **[Email Handling](MAIL.md)**: Skicka mejl via SMTP och mallar.
- **[HTTP System](HTTP.md)**: Djupdykning i Request, Response och Redirects.
- **[Security](SECURITY.md)**: CSP, CSRF-skydd och säker utdata.

## Utveckling & Bidrag
- **[Testing](TESTING.md)**: Hur du skriver och kör tester med PHPUnit.
- **[Contributing](CLI_CONTRIBUTING.md)**: Guide för att bygga ut CLI-verktyget.