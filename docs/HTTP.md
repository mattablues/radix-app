# HTTP (Radix Framework)

## Översikt
Här dokumenteras grunderna för:
- `Request` (uri, method, headers, ip, input)
- `Response` (status, headers, body)
- `JsonResponse` (JSON-body + content-type)

## Rekommendationer
- Returnera alltid en `Response` från controllers/handlers
- För API: använd `JsonResponse` och konsekvent JSON-format
- Sätt tydliga statuskoder (200/201/204/400/401/403/404/422/429/500)

## Headers
- `Content-Type`
- Cache headers när relevant
- Security headers via middleware (om ni använder det)

## Tips för testning
- Testa statuskod
- Testa viktiga headers
- Testa body-format (JSON decode)