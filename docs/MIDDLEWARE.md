# docs/MIDDLEWARE.md

← [`Tillbaka till index`](INDEX.md)

# Middleware (Radix App)

Middleware körs “runt” din route handler och kan t.ex.:

- stoppa requesten (t.ex. redirect till login eller returnera 401/403/429)
- modifiera request/response
- lägga till headers (CORS, security headers)
- logga requests
- rate limiting
- dela data till views (t.ex. current user)

---

## Pipeline-modellen (hur det fungerar)

Förenklat:

1) Request kommer in
2) Middleware A kör
3) Middleware B kör
4) Route handler/controller kör
5) Response går tillbaka genom middlewarekedjan

Middleware kan antingen:
- **släppa igenom** till nästa steg, eller
- **avbryta** och returnera en response direkt (t.ex. redirect)

---

## Var middleware ligger

I appen hittar du typiskt middleware-klasser under:

- `src/Middlewares/`

Och kopplingen “alias -> middleware”/vilka som körs brukar ligga i en konfigfil under `config/` (exakt filnamn beror på din setup).

---

## Koppla middleware till routes

Du kan lägga middleware på:

- en enskild route
- en hel route-grupp (t.ex. `/admin`)

Exakt API beror på din router, men principen är:

- “auth” på routes som kräver inloggning
- “admin” på routes som kräver admin-roll
- rate limit på känsliga endpoints (login, API)

---

## Exempel: Auth-middleware (koncept)

En auth-middleware gör ofta:

- kontrollera om sessionen är autentiserad
- om inte: sätt flash message och redirecta till login
- om ja: fortsätt till nästa middleware/handler

---

## Bra praxis

- Gör middleware små och ansvarsbundna (en sak per middleware)
- Undvik global state
- Logga och hantera fel konsekvent
- Skriv tester för både:
  - “stoppar tidigt” (returnerar response/redirect)
  - “passerar” (kallar nästa handler)

---

## Vanliga middleware-typer i en Radix-app

- `auth` (kräv inloggning)
- `cors` (API)
- `rate-limit` (API/login)
- `security-headers` (CSP, X-Frame-Options, osv.)
- `request-logger`
- `share-current-user` (dela user i views)
