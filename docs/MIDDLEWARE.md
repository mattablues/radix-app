# Middleware (Radix Framework)

## Översikt
Middleware körs “runt” din route handler och kan:
- stoppa requesten (t.ex. 401/403/429)
- modifiera request/response
- lägga till headers/loggning
- göra rate limiting, auth, CORS, security headers

## Var ligger koden?
- Middleware-klasser (t.ex. `framework/src/Middleware/...` och/eller `src/Middlewares/...`)
- Middleware-konfiguration (alias → klass)

## Pipeline-modell
- Request går in
- Middleware A → Middleware B → ... → Handler → tillbaka genom middleware

## Typiska middleware-exempel
- Auth (kräv inloggning)
- CORS
- Rate limiter
- Request logger
- Security headers

## Tips för bra middleware
- Gör dem små och ansvarsbundna
- Undvik global state
- Skriv tester för “stoppar” (returnerar response tidigt) och “passerar”