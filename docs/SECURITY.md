# Säkerhet (Radix Framework)

## Översikt
Säkerhet i Radix handlar ofta om:
- security headers (CSP, X-Frame-Options, etc.) via middleware
- CORS-regler för API
- rate limiting
- input-validering
- proxies/trusted headers

## Rekommendationer
- Ha en tydlig CORS-policy
- Slå på rate limiting för publika endpoints
- Logga fel och undvik att läcka stack traces i production
- Validera input strikt (API och formulär)

## CI
Säkerhetstester kan börja som enkla assertions:
- rätt headers finns
- CORS beter sig som förväntat
- rate limiting svarar 429 och rätt retry headers