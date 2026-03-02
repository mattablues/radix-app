# docs/ARCHITECTURE.md

← [`Tillbaka till index`](INDEX.md)

# Arkitekturöversikt (Radix App)

Detta dokument beskriver de tekniska principerna bakom Radix App och hur ett anrop (Request) normalt bearbetas.

---

## 1) Request-cykeln (översikt)

1. **Entry point**  
   Alla web-anrop börjar i `public/index.php`.

2. **Bootstrap**  
   Bootstrappen initierar containern och laddar konfiguration (t.ex. via `bootstrap/` och `config/`).

3. **Middleware**  
   Anropet passerar globala och/eller route-specifika middleware (t.ex. auth, rate limiting, security headers).

4. **Routing**  
   Router matchar URL + HTTP-metod mot en route och dess handler (controller/callable).

5. **Controller/Handler**  
   Din controller (under `src/Controllers`) kör logik, pratar med services/modeller och returnerar en `Response`.

6. **Rendering**  
   För web: vyer renderas via template-motorn (`.ratio.php`).  
   För API: JSON returneras via `JsonResponse`/API-controller.

---

## 2) Container & Service Providers

Appen använder en DI-container:

- “core wiring” sker vid boot (typiskt i `config/services.php`)
- providers (i `src/Providers/`) registreras enligt `config/providers.php`

Rekommendation:
- keep boot/wiring tydligt separerat
- flytta app-funktionalitet till providers och services

Se även:

- [`docs/SERVICES.md`](SERVICES.md)

---

## 3) Templates (rendering)

Templates använder:

- `.ratio.php`
- layouts (`{% extends %}`, `{% block %}`, `{% yield %}`)
- komponenter (`<x-...>`) och props (`{% props %}`)
- caching av kompilerade templates (för prestanda)

Se även:

- [`docs/TEMPLATES.md`](TEMPLATES.md)
- [`docs/FRONTEND.md`](FRONTEND.md)

---

## 4) Databas & ORM (hur det brukar kopplas in)

Databas och ORM används via modeller (t.ex. `src/Models`) och Radix QueryBuilder/ORM.

Se även:

- [`docs/DATABASE.md`](DATABASE.md)
- [`docs/ORM.md`](ORM.md)

---

## 5) “Moduler” via scaffolds

Radix App byggs stegvis via scaffolds (t.ex. `auth`, `user`, `admin`, `updates`), som kan lägga till:

- routes/controllers/views
- middleware/providers/listeners-konfig
- migrations/seeders

Efter att du installerat ett scaffold kör du typiskt:

```bash
php radix migrations:migrate
```

Se även:

- [`docs/CLI.md`](CLI.md)
