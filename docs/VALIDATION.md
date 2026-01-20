# Validering i Radix

Radix erbjuder ett kraftfullt valideringssystem via klassen `Radix\Support\Validator`. Det kan användas för att validera både formulärdata och API-anrop.

## Grundläggande användning

Validering sker vanligtvis i en Controller. Om valideringen misslyckas returneras felmeddelanden automatiskt till vyn eller som JSON (i API:er).

```php
$validator = new Validator($request->post, [
    'email'    => 'required|email',
    'password' => 'required|min:8|confirmed'
]);

if ($validator->validate()) {
    // Datan är giltig!
} else {
    $errors = $validator->errors();
}
```

## Tillgängliga regler

Här är några av de vanligaste valideringsreglerna:

- `required`: Fältet får inte vara tomt.
- `email`: Måste vara en giltig e-postadress.
- `min:num`: Minsta längd (sträng) eller minsta värde (numeriskt).
- `max:num`: Maximala längd eller värde.
- `confirmed`: Matchar fältet med ett annat fält som slutar på `_confirmation` (t.ex. `password` och `password_confirmation`).
- `unique:Model,column`: Kontrollerar att värdet är unikt i databasen för angiven modell och kolumn.
- `nullable`: Fältet får vara tomt, men om det fylls i måste övriga regler följas.
- `sometimes`: Validera fältet endast om det finns med i indatan.
- `numeric` / `integer`: Kontrollerar att värdet är ett tal eller heltal.
- `url` / `ip`: Validerar format för webbadresser eller IP-adresser.

## Nestlad data (Dot Notation)

Om din indata innehåller arrayer kan du använda punktnotation för att nå djupare nivåer:

```php
$rules = [
    'user.profile.bio' => 'required|max:500'
];
```

## Validering av filer

Du kan validera uppladdade filer baserat på typ och storlek (i MB):

```php
$rules = [
    'avatar' => 'file_type:image/jpeg,image/png|file_size:2'
];
```

## Säkerhet (Honeypot)

För att motverka spam i formulär stödjer Radix både enkla och dynamiska honeypots:

- `honeypot`: Kontrollerar att ett dolt fält är tomt.
- `honeypot_dynamic`: Kontrollerar att fältet matchar ett session-genererat ID.

## Översättningar

Ramverket har inbyggda översättningar för vanliga fältnamn (t.ex. `first_name` blir "förnamn" i felmeddelandet). Du kan se listan i `Validator.php` under `$fieldTranslations`.

### Exempel på felmeddelande:
> "Fältet förnamn är obligatoriskt."