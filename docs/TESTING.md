# Testning (Radix Framework)

## Översikt
- PHPUnit som test runner
- PHPStan för statisk analys
- (Valfritt) Infection för mutation testing

## Struktur
- `tests/` innehåller tester per område (Api/Http/Support/...)
- Använd `sys_get_temp_dir()` för temporära filer
- Städa i `tearDown()` för determinism

## Tips för stabila tester
- Undvik flakiga tidstester (sleep exakt N sekunder)
- Använd deterministiska asserts (t.ex. reflection för defaultvärden)
- Om du måste vänta: loopa med deadline i stället för en enda sleep

## Kör lokalt (PowerShell)

powershell composer install composer stan vendor/bin/phpunit -c phpunit.xml --do-not-cache-result