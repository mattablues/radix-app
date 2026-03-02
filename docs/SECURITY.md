# docs/SECURITY.md

← [`Tillbaka till index`](INDEX.md)

# Säkerhet (Radix App)

Den här guiden beskriver de viktigaste säkerhetsdelarna i Radix App och hur du tänker kring “secure defaults” i din app.

---

## Översikt

Säkerhet i Radix handlar ofta om:

- security headers (t.ex. CSP, X-Frame-Options, m.fl.) via middleware
- CORS-regler för API
- rate limiting
- input-validering
- proxies / trusted headers
- att inte läcka debug-info i production

---

## Rekommendationer (kort)

- Ha en tydlig CORS-policy (särskilt för API)
- Slå på rate limiting för publika endpoints (t.ex. login)
- Logga fel, men läck inte stack traces i production
- Validera input strikt (både API och formulär)
- Använd CSP om du vill höja ribban mot XSS

---

## CSP (Content Security Policy) och `nonce`

Om du kör CSP som kräver `nonce` på scripts behöver dina script-taggar ha ett `nonce`-attribut som matchar requestens nonce.

I templates kan det se ut så här:

```html
<script nonce="{{ secure_output(csp_nonce(), true) }}" src="{{ versioned_file('/js/app.js') }}"></script>
```

För mer om asset-versionering, se:

- [`docs/FRONTEND.md`](FRONTEND.md)

---

## CORS (API)

För API:er vill du ha en tydlig allow-origin policy:

- tillåt bara origins du faktiskt behöver
- undvik wildcard när du skickar credentials/cookies

Vanligt är att CORS hanteras via en listener/middleware så att du får korrekt preflight och headers på alla svar.

---

## Rate limiting

Rate limiting är extra viktigt för:

- login endpoints
- password reset
- publika API-endpoints

Målet är att få ett tydligt `429`-svar vid abuse och gärna logga detta så det går att följa upp.

---

## Trusted proxies

Om appen kör bakom reverse proxy/load balancer behöver du vara tydlig med vilka proxy-IPs som är “trusted”, annars kan IP-baserad logik (rate limiting, allowlists, audit logs) bli fel.

---

## Input-validering

Se:

- [`docs/VALIDATION.md`](VALIDATION.md)

---

## Production-checklista (snabb)

- `APP_DEBUG=0`
- CSP på (om du använder det) och nonce korrekt i templates
- CORS strikt konfigurerad
- rate limiting aktiverad för känsliga endpoints
- loggning på, utan att exponera känslig data
