# Konfiguration (.env / config) (Radix Framework)

## Översikt
Här dokumenteras:
- `.env` och `.env.example`
- vilka nycklar som är viktiga
- validering av env (om ni har EnvValidator)
- paths: cache/logs/storage

## Rekommendationer
- `.env` ska inte committas
- håll `.env.example` uppdaterad
- validera env tidigt vid boot så fel syns direkt

## CI
I CI kan du sätta minimala env-variabler för test (t.ex. `APP_ENV=development`).