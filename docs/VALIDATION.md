# docs/VALIDATION.md

← [`Tillbaka till index`](INDEX.md)

# Validering (Radix App)

Radix erbjuder ett valideringssystem via `Radix\Support\Validator`.  
Det kan användas för att validera både formulärdata (web) och payloads (API).

---

## Grundläggande användning

```php
<?php

use Radix\Support\Validator;

/** @var array<string,mixed> $data */
$data = $request->post;

$validator = new Validator($data, [
    'email'    => 'required|email',
    'password' => 'required|min:8|confirmed',
]);

if ($validator->validate()) {
    // Datan är giltig!
} else {
    $errors = $validator->errors();
}
```

---

## Vanliga regler (urval)

- `required` — fältet får inte vara tomt
- `email` — måste vara en giltig e-postadress
- `min:num` — minsta längd (sträng) eller minsta värde (numeriskt)
- `max:num` — maximal längd eller värde
- `confirmed` — matchar fältet mot `<fält>_confirmation`
- `unique:Model,column` — kontrollerar att värdet är unikt i DB för given modell/kolumn
- `nullable` — fältet får vara tomt, men om det finns måste övriga regler passa
- `sometimes` — validera bara om fältet finns i indata
- `numeric` / `integer` — validerar numeriskt/heltal
- `url` / `ip` — validerar URL/IP-format
- `regex:<pattern>` — matchar mot regex-mönster
- `in:a,b,c` / `not_in:a,b,c` — whitelist/blacklist
- `boolean` — `true/false/1/0`
- `date` / `date_format:<format>` — datumvalidering
- `starts_with:a,b` / `ends_with:a,b` — prefix/suffix
- `required_with:field1,field2` — krävs om något av fälten har värde
- `file_type:...` / `file_size:num` — filtyp och maxstorlek (MB)
- `honeypot` / `honeypot_dynamic` — anti-spam

---

## Felhantering: `errors()`

Efter `validate()` kan du hämta fel som en array:

```php
<?php

$errors = $validator->errors();

// Exempelstruktur:
// [
//   'email' => ['Fältet e-post är obligatoriskt.'],
//   'password' => ['Fältet lösenord måste vara minst 8 tecken långt.'],
// ]
```

---

## Nestlad data (dot-notation)

Om din indata innehåller arrayer kan du använda punktnotation:

```php
<?php

$rules = [
    'user.profile.bio' => 'required|max:500',
];
```

---

## Validering av filer

Validera uppladdade filer baserat på typ och storlek (MB):

```php
<?php

$rules = [
    'avatar' => 'nullable|file_type:image/jpeg,image/png|file_size:2',
];
```

> Tips: kombinera ofta med `nullable` så att “ingen fil uppladdad” inte blir ett fel.

---

## Anti-spam (honeypot)

För att motverka spam i formulär stödjer Radix:

- `honeypot` — kontrollerar att ett dolt fält är tomt
- `honeypot_dynamic` — kontrollerar att fältet matchar ett session-genererat ID

Praktiskt upplägg:
1) rendera ett dolt inputfält i formuläret
2) validera med `honeypot_dynamic`
3) om regeln faller: användaren får ett generellt formulärfel (i stället för att avslöja att det var honeypot som triggade)

---

## Fältnamn i felmeddelanden (översättningar)

Validatorn har inbyggda översättningar för vanliga fält (t.ex. `email` → “e-post”).  
Du kan även **override:a** dessa i appen, så att felmeddelanden använder era egna “labels”.

### Hur override fungerar

- Appen kan sätta en global override för fältöversättningar via config (t.ex. `translations.validations`).
- Vid boot kan en provider läsa configen och koppla in override:n i `Validator`.

Exempel på vad du kan lägga i config:

```php
<?php

return [
    'translations' => [
        'validations' => [
            'email' => 'mejl',
            'first_name' => 'förnamn',
        ],
    ],
];
```

### Viktigt: endast `string => string` används

För att undvika trasiga felmeddelanden filtreras override-konfigen:

- nycklar måste vara strängar
- värden måste vara strängar
- ogiltiga entries ignoreras

Det betyder t.ex. att `email => 123` **inte** får effekt (och ska inte få effekt).

---

## Tips: validering i controllers vs Form Requests

- För små formulär: validera direkt i kontrollern med `Validator`
- För återanvändbar/renare kod: använd en “Form Request”-klass (via `make:form-request`) och låt den kapsla in:
  - input accessors (t.ex. `email()`, `password()`)
  - regler
  - validering + errors

Det håller controllers tunna och gör validering lättare att testa.

---

## Se även

- Templates & formulär: [`docs/TEMPLATES.md`](TEMPLATES.md)
- Säkerhet: [`docs/SECURITY.md`](SECURITY.md)
- ORM (för `unique`-regeln): [`docs/ORM.md`](ORM.md)
