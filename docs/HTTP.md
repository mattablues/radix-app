# docs/HTTP.md

← [`Tillbaka till index`](INDEX.md)

# HTTP (Radix App)

Den här guiden beskriver grunderna i HTTP-lagret: request/response och hur du tänker kring statuskoder och JSON-svar i en Radix-app.

---

## Översikt

Här är huvuddelarna:

- `Request` (URI, method, headers, IP, input, session)
- `Response` (status, headers, body)
- `JsonResponse` (JSON-body + `Content-Type: application/json`)

---

## Request

En `Request` representerar inkommande HTTP-anrop.

Typiska saker du vill hämta:

- method (GET/POST/PUT/DELETE)
- uri/path
- headers
- client-ip (tänk på proxies)
- input (query/body)
- session (för web)

> Tips: om du kör bakom reverse proxy, se `docs/SECURITY.md` om trusted proxies, annars kan IP-baserad logik bli fel.

---

## Response

En `Response` är det du returnerar från controllers/handlers.

Rekommendationer:

- returnera alltid en `Response` (eller en specialiserad response som JSON/redirect)
- sätt tydliga statuskoder
- håll headers konsekventa (gärna via middleware/listeners)

---

## JsonResponse (API)

För API-svar ska du använda JSON-responses och konsekventa statuskoder.

Vanliga statuskoder:

- `200` OK
- `201` Created
- `204` No Content
- `400` Bad Request
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Unprocessable Entity (valideringsfel)
- `429` Too Many Requests (rate limit)
- `500` Server Error

---

## Headers (bra att ha koll på)

- `Content-Type`
- cache headers när relevant
- CORS headers (API)
- security headers (CSP, m.m.)

Ofta är det bäst att hantera detta via middleware/listeners så att det blir samma överallt.

---

## Tips för testning

När du testar endpoints:

- testa statuskoden
- testa viktiga headers
- testa body-format
  - för JSON: JSON-dekoda och assert:a på struktur/data
