# docs/COOKBOOK.md

← [`Tillbaka till index`](INDEX.md)

# Radix Cookbook (Radix App)

Detta dokument innehåller snabba recept för vanliga utvecklingsuppgifter i Radix App.

---

## Skapa en ny sida (end-to-end)

1) **Skapa modell** (om sidan behöver data)  
   Lägg en modell i `src/Models/`.

2) **Skapa controller**  
   Skapa en controller i `src/Controllers/` som ärver `AbstractController`.

3) **Definiera route**  
   Lägg till routen i `routes/web.php`.

4) **Skapa vy**  
   Skapa en vy i `views/`, t.ex. `views/ny-sida.ratio.php`.

5) **Validering (om du tar emot input)**  
   Validera i kontrollern eller via en form request. Se `docs/VALIDATION.md`.

---

## Lägga till ett nytt CLI-kommando

Rekommenderat sätt i Radix App:

1) Skapa ett nytt kommando med generatorn:

```bash
php radix make:command UsersSyncCommand
```

2) Om du vill välja CLI-namn själv:

```bash
php radix make:command UsersSyncCommand --command=users:sync
```

3) Om kommandot inte dyker upp (eller efter större ändringar), rensa cache:

```bash
php radix cache:clear
```

Se även:

- [`docs/CLI.md`](CLI.md)

---

## Release & deployment (snabb checklista)

### Miljö

I production vill du normalt ha:

- `APP_ENV=production`
- `APP_DEBUG=0`
- templates/view-cache aktiverad (för prestanda)

### Deployment-kommandon (exempel)

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php radix cache:clear
```

> Om du använder deploy-säkerhet för CLI i production kan du behöva köra migrations med deploy-flagga/variabel enligt din policy.

### Rättigheter

Säkerställ att webbservern kan skriva till:

- `storage/`
- `cache/`

---

## Utvecklingsflöde (snabbt)

Innan du pushar:

### Tester

```bash
composer test
```

### Statisk analys

```bash
composer stan
```

### Mutation testing (valfritt)

```bash
composer infect
```

### Kodstil

```bash
composer format
composer format:check
```

---

## Arbeta med vyer & cache

Om du ändrar templates/komponenter och inte ser effekten direkt:

1) rensa cache:

```bash
php radix cache:clear
```

2) kontrollera att du kör rätt miljö (`APP_ENV`, debug/cache-beteende)
