# docs/TESTING.md

← [`Tillbaka till index`](INDEX.md)

# Testning (Radix App)

Den här guiden beskriver hur du kör och skriver tester i **Radix App**.

---

## Översikt

Projektet använder:

- **PHPUnit** som test runner
- **PHPStan** för statisk analys
- (Valfritt) **Infection** för mutation testing
- **PHP-CS-Fixer** för kodstil

---

## Kör lokalt (rekommenderat via Composer scripts)

Projektet har färdiga scripts i `composer.json` så att du slipper komma ihåg långa kommandon.

### Tester (PHPUnit)

```bash
composer test
```

### Statisk analys (PHPStan)

Full analys (via projektets wrapper):

```bash
composer stan
```

Minimal analys:

```bash
composer stan:minimal
```

### Mutation testing (Infection)

```bash
composer infect
```

Med PCOV:

```bash
composer infect:pcov
```

Med Xdebug:

```bash
composer infect:xdebug
```

### Kodstil (PHP-CS-Fixer)

Auto-fix:

```bash
composer format
```

Bara kontroll (bra i CI):

```bash
composer format:check
```

---

## Kör direkt (om du vill)

### PHPUnit

```bash
php -d xdebug.mode=off vendor/bin/phpunit
```

### PHPStan (direkt)

```bash
vendor/bin/phpstan analyse -c phpstan.minimal.neon
```

---

## Struktur

- `tests/` innehåller tester (ofta per område)
- Använd `sys_get_temp_dir()` för temporära filer i tester
- Städa upp i `tearDown()` så tester inte påverkar varandra (särskilt om du har statiskt state)

---

## Tips för stabila tester

- Undvik flakiga tidstester (t.ex. `sleep(1)` och hoppas på det bästa)
- Använd deterministiska asserts
- Om du måste vänta på något: loopa med en deadline i stället för en enda lång `sleep`

---

## Vanligt felsök-flöde

1) Kör om bara en testfil:

```bash
vendor/bin/phpunit -c phpunit.xml tests/<fil>.php
```

2) Kör PHPStan om du får typfel/kontraktsfel:

```bash
composer stan
```
