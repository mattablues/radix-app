# Radix Cookbook (COOKBOOK.md)

Detta dokument innehåller snabba recept för vanliga utvecklingsuppgifter i Radix.

## Skapa en ny sida (End-to-End)
1. **Skapa Modell**: Om sidan behöver data, skapa en fil i `src/Models/`.
2. **Skapa Controller**: Skapa `src/Controllers/NyController.php` som ärver `AbstractController`.
3. **Definiera Route**: Lägg till rutten i `routes/web.php`.
4. **Skapa Vy**: Skapa `views/ny-sida.ratio.php`.
5. **Validering**: Använd `Radix\Support\Validator` i kontrollern för inkommande POST-data.

## Lägga till ett nytt CLI-kommando
1. Skapa en ny klass i `src/Commands/` (eller motsvarande mapp).
2. Registrera kommandot i `radix`-binären eller via en Service Provider.
3. Kör `php radix --md > docs/CLI.md` för att uppdatera dokumentationen.

---

# Release & Deployment (RELEASES.md)

Checklista för att ta Radix från utveckling till produktion.

## 1. Miljökonfiguration (.env)
Säkerställ följande värden i produktion:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `VIEWS_CACHE_PATH=cache/views`

## 2. Optimering
Kör dessa kommandon på servern vid deployment:
- `composer install --no-dev --optimize-autoloader`
- `npm ci && npm run build` (om frontend ändrats)
- `php radix cache:clear` (om du har ett sådant kommando)

## 3. Rättigheter
Säkerställ att webbservern kan skriva till:
- `storage/`
- `cache/`

---

# Utvecklingsflöde (DEVELOPMENT_FLOW.md)

Hur vi arbetar effektivt med Radix-templaten.

## 1. Test-Driven Development (TDD)
Vi strävar efter hög testtäckning. Innan du pushar:
- Kör PHPUnit: `vendor/bin/phpunit`
- Kör Infection för att hitta svaga tester: `composer infect`
- Kontrollera statisk analys: `composer stan`

## 2. Kodstil (Linting)
Vi använder PHP-CS-Fixer. Fixa formateringen automatiskt med:
- `composer format` (om scriptet finns i composer.json) eller kör `.php-cs-fixer.dist.php`.

## 3. Arbeta med Vyer & Cache
Om du gör ändringar i `RadixTemplateViewer` eller ändrar logik i komponenter och inte ser ändringen:
- Rensa vyn-cachen manuellt i `cache/views/` eller använd rensningskommandot i din `commands`-fil.