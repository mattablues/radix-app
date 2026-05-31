# docs/VALIDATION.md

← [`Tillbaka till index`](INDEX.md)

# Validering (Radix App)

Radix App använder validering från **Radix Framework**, framför allt:

```php
Radix\Support\Validator
Radix\Http\FormRequest
```

Validering kan användas för:

- vanliga formulär
- API payloads
- uppladdade filer
- nested data
- anti-spam/honeypot
- form request-klasser
- controller-validering

---

## Grundprincip

Validera alltid användarinput innan du använder den.

Det gäller särskilt innan du:

- sparar i databas
- skickar mail
- laddar upp filer
- dispatchar events
- returnerar data till API
- använder input i redirects eller headers

---

## Validator

Grundläggande användning:

```php
<?php

declare(strict_types=1);

use Radix\Support\Validator;

/** @var array<string, mixed> $data */
$data = $request->post;

$validator = new Validator($data, [
    'email' => 'required|email',
    'password' => 'required|min:8|confirmed',
]);

if ($validator->validate()) {
    // Datan är giltig.
} else {
    $errors = $validator->errors();
}
```

---

## Regler som string eller array

Regler kan skrivas som pipe-separerad string:

```php
$rules = [
    'email' => 'required|email',
];
```

eller som array:

```php
$rules = [
    'email' => ['required', 'email'],
];
```

Båda formerna kan användas beroende på vad som passar bäst.

---

## Felhantering

Efter `validate()` kan du hämta fel med:

```php
$errors = $validator->errors();
```

Exempelstruktur:

```php
[
    'email' => [
        'Fältet e-post är obligatoriskt.',
    ],
    'password' => [
        'Fältet lösenord måste vara minst 8 tecken långt.',
    ],
]
```

I en controller kan du till exempel skicka felen tillbaka till en view:

```php
return $this->view('contact.index', [
    'errors' => $errors,
]);
```

---

## Vanliga regler

Vanliga regler:

```text
required
string
email
min:num
max:num
confirmed
nullable
sometimes
numeric
integer
url
ip
regex:<pattern>
match:<pattern>
in:a,b,c
not_in:a,b,c
boolean
date
date_format:<format>
starts_with:a,b
ends_with:a,b
required_with:field1,field2
file_type:...
file_size:num
honeypot
honeypot_dynamic
unique:Model,column
```

Exakt regelstöd kan bero på framework-version.

---

## `required`

Fältet måste finnas och ha värde:

```php
$rules = [
    'name' => 'required',
];
```

---

## `email`

Fältet måste vara en giltig e-postadress:

```php
$rules = [
    'email' => 'required|email',
];
```

---

## `min` och `max`

För strängar kontrolleras längd.

För numeriska värden kontrolleras värde.

```php
$rules = [
    'password' => 'required|min:8',
    'name' => 'required|max:100',
];
```

---

## `confirmed`

`confirmed` jämför fältet med ett confirmation-fält.

Exempel:

```php
$rules = [
    'password' => 'required|min:8|confirmed',
];
```

Förväntar normalt:

```text
password
password_confirmation
```

---

## `nullable`

`nullable` betyder att fältet får vara tomt.

Om värde finns ska övriga regler fortfarande gälla.

```php
$rules = [
    'phone' => 'nullable|string|max:50',
];
```

---

## `sometimes`

`sometimes` betyder att fältet bara valideras om det finns i input.

```php
$rules = [
    'nickname' => 'sometimes|string|max:50',
];
```

---

## Numeriska regler

```php
$rules = [
    'age' => 'integer|min:18',
    'price' => 'numeric|min:0',
];
```

---

## URL och IP

```php
$rules = [
    'website' => 'nullable|url',
    'ip_address' => 'nullable|ip',
];
```

---

## Regex och match

Regex:

```php
$rules = [
    'username' => 'required|regex:/^[a-z0-9_]+$/i',
];
```

Match:

```php
$rules = [
    'code' => 'required|match:/^[A-Z0-9]{6}$/',
];
```

Tänk på att regex-regler kan behöva skrivas som array om pattern innehåller pipe-tecken.

Exempel:

```php
$rules = [
    'value' => ['required', 'regex:/^(foo|bar)$/'],
];
```

---

## `in` och `not_in`

Whitelist:

```php
$rules = [
    'role' => 'required|in:admin,editor,user',
];
```

Blacklist:

```php
$rules = [
    'status' => 'not_in:blocked,deleted',
];
```

---

## Boolean

```php
$rules = [
    'newsletter' => 'boolean',
];
```

Vanliga tillåtna värden kan vara:

```text
true
false
1
0
```

beroende på implementation.

---

## Date och date_format

```php
$rules = [
    'published_at' => 'nullable|date',
    'birthday' => 'nullable|date_format:Y-m-d',
];
```

---

## Starts with och ends with

```php
$rules = [
    'sku' => 'required|starts_with:RAD,SKU',
    'filename' => 'required|ends_with:.jpg,.png',
];
```

---

## Required with

Fältet krävs om något av angivna fält har värde:

```php
$rules = [
    'phone' => 'required_with:contact_by_phone',
];
```

---

## Unique

`unique` kontrollerar att ett värde är unikt i databasen för en modell/kolumn.

Exempel:

```php
$rules = [
    'email' => 'required|email|unique:User,email',
];
```

Exakt modellupplösning beror på ORM/config.

Se mer i:

- [`ORM.md`](ORM.md)
- [`DATABASE.md`](DATABASE.md)

---

## Dot-notation för nested data

Om input innehåller arrayer kan du använda dot-notation:

```php
$rules = [
    'user.profile.bio' => 'required|max:500',
];
```

Exempeldata:

```php
$data = [
    'user' => [
        'profile' => [
            'bio' => 'Hej!',
        ],
    ],
];
```

---

## Validering av filer

Uppladdade filer kan valideras med regler som:

```text
file_type
file_size
```

Exempel:

```php
$rules = [
    'avatar' => 'nullable|file_type:image/jpeg,image/png|file_size:2',
];
```

`file_size:2` betyder normalt max 2 MB.

Rekommendationer:

- kombinera ofta med `nullable`
- kontrollera MIME/content type
- kontrollera storlek
- byt filnamn vid lagring
- spara inte uploads tillsammans med appens betrodda assets
- tillåt inte exekverbara filtyper

Se mer i:

- [`FILES.md`](FILES.md)
- [`IMAGES.md`](IMAGES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Anti-spam med honeypot

Radix Validator stödjer honeypot-regler:

```text
honeypot
honeypot_dynamic
```

### `honeypot`

Kontrollerar normalt att ett dolt fält är tomt.

Exempel:

```php
$rules = [
    'website' => 'honeypot',
];
```

### `honeypot_dynamic`

Kontrollerar normalt ett dynamiskt honeypot-fält, till exempel ett session-genererat fältnamn.

Exempel:

```php
$rules = [
    'hp_field' => 'honeypot_dynamic',
];
```

Praktiskt upplägg:

1. generera ett honeypot-fält i formuläret
2. rendera fältet dolt
3. validera med `honeypot_dynamic`
4. returnera ett generellt formulärfel om det triggas

Poängen är att inte avslöja exakt vilken anti-spam-regel som stoppade requesten.

---

## FormRequest

För renare controllers kan du använda `FormRequest`.

Basen finns i:

```php
Radix\Http\FormRequest
```

Appens egna request-klasser ligger normalt i:

```text
src/Requests/
```

Skapa via CLI:

```bash
php radix make:form-request ContactRequest
```

Kör hjälp:

```bash
php radix make:form-request --help
```

---

## Exempel: FormRequest

```php
<?php

declare(strict_types=1);

namespace App\Requests;

use Radix\Http\FormRequest;

final class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'message' => 'required|string|max:5000',
        ];
    }

    public function email(): string
    {
        return isset($this->validated()['email'])
            ? (string) $this->validated()['email']
            : '';
    }
}
```

---

## Använda FormRequest i controller

```php
public function create(): \Radix\Http\Response
{
    $this->before();

    $form = new \App\Requests\ContactRequest($this->request);

    if (!$form->validate()) {
        return $this->view('contact.index', [
            'errors' => $form->errors(),
        ]);
    }

    $data = $form->validated();

    // använd validerad data

    return redirect(route('home.index'));
}
```

---

## `validated()`

`FormRequest::validated()` returnerar endast fält som har regler definierade.

Exempel:

```php
$data = $form->validated();
```

Det minskar risken att oönskade fält från requesten används.

---

## Extra regler i FormRequest

Du kan lägga till extra regler genom att overrida:

```php
protected function addExtraRules(array $rules): array
```

Exempel:

```php
protected function addExtraRules(array $rules): array
{
    $rules['honeypot'] = 'honeypot';

    return $rules;
}
```

---

## Hantera valideringsfel i FormRequest

Du kan reagera på valideringsfel genom att overrida:

```php
protected function handleValidationErrors(): void
```

Exempel:

```php
protected function handleValidationErrors(): void
{
    // Logga spamförsök, normalisera fel eller sätt generellt formulärfel.
}
```

---

## Validering i controller

För små formulär kan det vara okej att validera direkt i controller:

```php
use Radix\Support\Validator;

$validator = new Validator($this->request->post, [
    'email' => 'required|email',
    'message' => 'required|max:5000',
]);

if (!$validator->validate()) {
    return $this->view('contact.index', [
        'errors' => $validator->errors(),
    ]);
}
```

För större formulär rekommenderas FormRequest.

---

## Validering i API controllers

För API:er bör valideringsfel returneras som JSON med en tydlig statuskod.

Vanligt:

```text
422 Unprocessable Entity
```

Exempelstruktur:

```json
{
  "message": "Validation failed",
  "errors": {
    "email": [
      "Fältet e-post är obligatoriskt."
    ]
  }
}
```

Se mer i:

- [`API.md`](API.md)
- [`HTTP.md`](HTTP.md)

---

## Fältnamn och översättningar

Validatorn har inbyggda översättningar för vanliga fält, till exempel:

```text
email -> e-post
first_name -> förnamn
last_name -> efternamn
password -> lösenord
```

Det gör att felmeddelanden blir mer användarvänliga.

---

## Override av fältöversättningar

Appen kan override:a fältöversättningar via config.

Exempel:

```php
<?php

declare(strict_types=1);

return [
    'translations' => [
        'validations' => [
            'email' => 'mejl',
            'first_name' => 'förnamn',
            'last_name' => 'efternamn',
        ],
    ],
];
```

Vid boot kan en provider läsa configen och koppla in override:n i Validator.

---

## Endast `string => string` används

När fältöversättningar sätts filtreras configen.

Endast entries där både nyckel och värde är strings används.

Giltigt:

```php
[
    'email' => 'mejl',
]
```

Ignoreras:

```php
[
    'email' => 123,
    10 => 'namn',
]
```

Det skyddar mot trasiga felmeddelanden.

---

## Dot-notation och fältöversättning

Vid dot-notation försöker validatorn först hitta exakt fältnamn.

Om det inte finns försöker den med sista segmentet.

Exempel:

```text
results.12.1.3.horse_name
```

kan falla tillbaka till:

```text
horse_name
```

om det finns en översättning för `horse_name`.

---

## CSRF är inte samma sak som validering

CSRF skyddar mot cross-site request forgery.

Validering kontrollerar att input är korrekt.

För write-actions i web controllers bör du normalt göra båda:

```php
$this->before();

$form = new ContactRequest($this->request);

if (!$form->validate()) {
    // ...
}
```

Se mer i:

- [`SECURITY.md`](SECURITY.md)
- [`CONTROLLERS.md`](CONTROLLERS.md)

---

## Validering och ORM

Validering ska ske innan data sparas med ORM.

Exempel:

```php
$form = new UserRequest($this->request);

if (!$form->validate()) {
    return $this->view('users.edit', [
        'errors' => $form->errors(),
    ]);
}

$user->fill($form->validated());
$user->save();
```

Se mer i:

- [`ORM.md`](ORM.md)

---

## Validering och uploads

Validering av uploads är extra viktigt.

Exempel:

```php
$rules = [
    'avatar' => 'nullable|file_type:image/jpeg,image/png|file_size:2',
];
```

Efter validering bör du fortfarande tänka på:

- byt filnamn
- välj säker katalog
- kontrollera filinnehåll om möjligt
- undvik att servera potentiellt farliga filer som körbar kod
- spara metadata/path i databasen

Se mer i:

- [`FILES.md`](FILES.md)
- [`IMAGES.md`](IMAGES.md)

---

## Bra praxis

- validera all användarinput
- använd FormRequest för större formulär
- använd `validated()` i stället för rå POST-data när möjligt
- använd `nullable` för frivilliga fält
- använd array-regler när regex innehåller `|`
- returnera `422` för API-valideringsfel
- visa generella fel vid honeypot/spam
- håll fältöversättningar uppdaterade i config
- validera uploads extra noggrant
- lita inte på client-side validation

---

## Felsökning

### Regeln körs inte

Kontrollera:

- att fältnamnet matchar input
- att regeln är rättstavad
- att fältet finns i `$data`
- om du använder `sometimes`

### `nullable` fungerar inte som väntat

Kontrollera regelordningen och att tomma värden faktiskt är `null` eller tom string beroende på implementation.

### Regex fungerar inte

Om regex innehåller `|`, skriv reglerna som array:

```php
$rules = [
    'field' => ['required', 'regex:/^(foo|bar)$/'],
];
```

### File validation failar alltid

Kontrollera:

- att input är från `$_FILES`
- att fältet har `tmp_name`
- att filen faktiskt är uppladdad
- att MIME-typen matchar exakt
- att `file_size` anges i MB

### Unique hittar inte modellen

Kontrollera:

- modellnamespace
- ORM resolver
- `ORM_MODEL_NAMESPACE`
- att modellen finns och är autoloadad

### Fältnamn översätts inte

Kontrollera config för validation translations och att provider/bootstrap kopplar in den.

### FormRequest ger tom `validated()`

`validated()` returnerar fält som finns i `rules()`.

Kontrollera att dina fält finns där.

---

## Relaterat

- [`CONTROLLERS.md`](CONTROLLERS.md)
- [`HTTP.md`](HTTP.md)
- [`ORM.md`](ORM.md)
- [`DATABASE.md`](DATABASE.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`FILES.md`](FILES.md)
- [`IMAGES.md`](IMAGES.md)
- [`SECURITY.md`](SECURITY.md)
- [`API.md`](API.md)
