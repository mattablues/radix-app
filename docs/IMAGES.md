# docs/IMAGES.md

← [`Tillbaka till index`](INDEX.md)

# Bilder & uppladdningar (Radix App)

Radix App använder fil- och bildstöd från **Radix Framework**.

Viktiga klasser:

```php
Radix\File\Upload
Radix\File\Image
```

Dessa används för att:

- validera uppladdade filer
- flytta uppladdade filer
- generera säkra filnamn
- behandla bilder med GD
- ändra storlek
- beskära
- rotera
- lägga på filter
- lägga till watermark

---

## Appens struktur för bilder

Radix App skiljer mellan:

```text
public/assets  = appens egna betrodda assets
public/uploads = användargenererade filer
```

Exempel:

```text
public/
  assets/
    images/
      graphics/
    favicons/
  uploads/
    users/
      1/
        avatar.jpg
```

---

## Assets vs uploads

### `public/assets`

Här lägger du appens egna statiska filer:

```text
public/assets/css/app.css
public/assets/js/app.js
public/assets/images/graphics/avatar.png
public/assets/favicons/favicon.svg
```

Det är betrodda filer som följer med appen.

### `public/uploads`

Här lägger du användargenererat innehåll:

```text
public/uploads/users/1/avatar.jpg
```

Uploads ska hanteras striktare eftersom de kommer från användare.

Se även:

- [`FILES.md`](FILES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Default-avatar

Radix App kan använda en default-avatar som app-asset:

```text
public/assets/images/graphics/avatar.png
```

Publik path:

```text
/assets/images/graphics/avatar.png
```

Exempel i template:

```html
<img src="{{ versioned_file('/assets/images/graphics/avatar.png') }}" alt="Avatar">
```

Med fallback från användaravatar:

```html
<img
    src="{{ versioned_file($currentUser->getAttribute('avatar'), '/assets/images/graphics/avatar.png') }}"
    alt="Avatar"
>
```

---

## Publika paths

För uploads bör du normalt spara publik path i databasen, inte absolut serverpath.

Exempel på fil på disk:

```text
public/uploads/users/1/avatar.jpg
```

Publik path att spara i databasen:

```text
/uploads/users/1/avatar.jpg
```

Det gör det enklare att rendera bilden:

```html
<img src="{{ versioned_file($user->getAttribute('avatar'), '/assets/images/graphics/avatar.png') }}" alt="Avatar">
```

---

## Uppladdning med `Upload`

`Radix\File\Upload` hanterar uppladdade filer.

Den kan:

- validera filen via `Validator`
- skapa uppladdningsmapp vid behov
- generera säkert unikt filnamn
- flytta filen med `move_uploaded_file`
- returnera sökväg till sparad fil
- behandla uppladdad fil som bild

---

## Grundexempel: upload

```php
<?php

declare(strict_types=1);

use Radix\File\Upload;

/** @var array<string, mixed> $file */
$file = $request->files['avatar'];

$upload = new Upload($file, ROOT_PATH . '/public/uploads/users/1');

if (!$upload->validate([
    'tmp_name' => 'required',
    'avatar' => 'file_type:image/jpeg,image/png,image/webp|file_size:2',
])) {
    $errors = $upload->getErrors();

    // hantera valideringsfel
}

$path = $upload->save();
```

`save()` returnerar absolut eller lokal path beroende på vilken upload directory du skickade in.

---

## Viktigt om validering av upload

`Upload::validate()` använder `Validator` på fil-arrayen.

Det betyder att reglerna behöver matcha hur din fil-array ser ut.

För vanlig form-validering är det ofta enklare att validera via en FormRequest eller Validator med hela requestens file-input.

Exempel:

```php
$validator = new \Radix\Support\Validator([
    'avatar' => $request->files['avatar'] ?? null,
], [
    'avatar' => 'nullable|file_type:image/jpeg,image/png,image/webp|file_size:2',
]);
```

Sedan kan du använda `Upload` för att spara filen.

Se mer i:

- [`VALIDATION.md`](VALIDATION.md)

---

## Spara med eget filnamn

Du kan skicka filnamn till `save()`:

```php
$path = $upload->save('avatar.jpg');
```

Filnamn saneras.

Otillåtna filnamn stoppas, till exempel:

```text
../avatar.jpg
avatar.php
avatar name.jpg
```

Rekommenderat är ändå att låta systemet generera säkra namn när det går.

---

## Genererade filnamn

Om du inte skickar filnamn genererar `Upload` ett unikt namn baserat på MIME-typ.

Exempel:

```text
f4c1b6e9272f8c13e8d8f5b01b3f3e10.jpg
```

Tillåtna bild-MIME-typer för automatisk extension är normalt:

```text
image/jpeg -> jpg
image/png  -> png
image/gif  -> gif
image/webp -> webp
```

---

## Bildbehandling med `processImage`

`Upload` kan behandla uppladdningen som bild innan den sparas:

```php
<?php

declare(strict_types=1);

use Radix\File\Image;
use Radix\File\Upload;

/** @var array<string, mixed> $file */
$file = $request->files['avatar'];

$upload = new Upload($file, ROOT_PATH . '/public/uploads/users/1');

$path = $upload->processImage(function (Image $image): void {
    $image->resizeImage(200, 200, 'crop');
});
```

Med eget output-filnamn:

```php
$path = $upload->processImage(function (Image $image): void {
    $image->resizeImage(200, 200, 'crop');
}, 'avatar.jpg');
```

---

## Bildbehandling med `Image`

`Radix\File\Image` är en wrapper runt PHP GD.

Exempel:

```php
<?php

declare(strict_types=1);

use Radix\File\Image;

$image = new Image(ROOT_PATH . '/public/uploads/source.jpg');

$image->resizeImage(800, 600, 'auto');

$image->saveImage(ROOT_PATH . '/public/uploads/resized.jpg', quality: 80);
```

---

## Resize

Ändra storlek:

```php
$image->resizeImage(800, 600, 'auto');
```

Vanliga resize-lägen:

```text
auto
portrait
landscape
exact
crop
```

### `auto`

Väljer proportioner automatiskt.

```php
$image->resizeImage(800, 600, 'auto');
```

### `portrait`

Prioriterar höjd.

```php
$image->resizeImage(400, 800, 'portrait');
```

### `landscape`

Prioriterar bredd.

```php
$image->resizeImage(1200, 600, 'landscape');
```

### `exact`

Tvingar exakta dimensioner.

```php
$image->resizeImage(300, 300, 'exact');
```

### `crop`

Beskär till exakta mått centrerat.

```php
$image->resizeImage(200, 200, 'crop');
```

---

## Spara bild

Spara bild:

```php
$image->saveImage(ROOT_PATH . '/public/uploads/image.jpg');
```

Med kvalitet:

```php
$image->saveImage(ROOT_PATH . '/public/uploads/image.jpg', quality: 80);
```

Kvalitet är framför allt relevant för JPEG/WebP beroende på implementation och format.

---

## Rotera bild

```php
$image->rotateImage(90);
```

Med bakgrundsfärg:

```php
$image->rotateImage(90, bgColor: 0);
```

---

## Grayscale

```php
$image->applyGrayscale();
```

---

## Watermark

Lägg till watermark:

```php
$image->addWatermark(ROOT_PATH . '/public/assets/images/graphics/logo.png', x: 10, y: 10);
```

---

## Rekommenderat: UploadService

För att hålla controllers tunna rekommenderas ofta en app-service, till exempel:

```text
src/Services/UploadService.php
```

En sådan service kan ansvara för:

- validering
- katalogstruktur
- filnamn
- bildbehandling
- konvertering från absolut path till publik path
- radering av gammal fil
- fallback-logik

---

## Exempel: Avatar-service

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Radix\File\Image;
use Radix\File\Upload;
use RuntimeException;

final class UploadService
{
    public function uploadAvatar(array $file, int $userId): string
    {
        $directory = ROOT_PATH . '/public/uploads/users/' . $userId;

        $validator = new \Radix\Support\Validator([
            'avatar' => $file,
        ], [
            'avatar' => 'required|file_type:image/jpeg,image/png,image/webp|file_size:2',
        ]);

        if (!$validator->validate()) {
            throw new RuntimeException('Invalid avatar upload.');
        }

        $upload = new Upload($file, $directory);

        $path = $upload->processImage(function (Image $image): void {
            $image->resizeImage(200, 200, 'crop');
        });

        return $this->toPublicPath($path);
    }

    private function toPublicPath(string $absolutePath): string
    {
        $publicRoot = rtrim(ROOT_PATH . '/public', '/\\');
        $normalized = str_replace('\\', '/', $absolutePath);
        $public = str_replace('\\', '/', $publicRoot);

        if (!str_starts_with($normalized, $public)) {
            throw new RuntimeException('Upload path is outside public directory.');
        }

        return substr($normalized, strlen($public));
    }
}
```

---

## Använda UploadService i controller

```php
public function updateAvatar(): \Radix\Http\Response
{
    $this->before();

    $file = $this->request->files['avatar'] ?? null;

    if (!is_array($file)) {
        return redirect(route('user.edit'));
    }

    $avatarPath = $this->uploads->uploadAvatar($file, $this->currentUser->id);

    $this->currentUser->avatar = $avatarPath;
    $this->currentUser->save();

    return redirect(route('user.index'));
}
```

---

## Säkerhet för uploads

### Validera alltid

Använd regler som:

```text
file_type:image/jpeg,image/png,image/webp
file_size:2
```

Exempel:

```php
$rules = [
    'avatar' => 'required|file_type:image/jpeg,image/png,image/webp|file_size:2',
];
```

### Lita inte på originalnamn

Originalfilnamn kommer från användaren.

Använd genererade filnamn när det går.

### Spara inte uppladdningar i assets

Använd inte:

```text
public/assets/
```

för användaruppladdningar.

Använd:

```text
public/uploads/
```

### Begränsa filtyper

Tillåt bara filtyper du faktiskt behöver.

För avatars:

```text
image/jpeg
image/png
image/webp
```

### Servera inte farliga filer

Tillåt inte uppladdning av:

```text
.php
.phtml
.phar
.js
.html
.svg
```

om du inte har ett mycket tydligt och säkert skäl.

SVG kan innehålla script och bör behandlas extra försiktigt.

---

## `.htaccess` för uploads

Radix App kan ha striktare `.htaccess`-regler i upload-katalogen.

Syfte:

- förhindra exekvering av scripts
- begränsa vilka filer som får serveras
- minska risken från användaruppladdningar

Se mer i:

- [`SECURITY.md`](SECURITY.md)

---

## Privata filer

Filer som inte ska vara publika bör inte ligga direkt under `public/`.

Lägg dem hellre utanför webroot, till exempel:

```text
storage/private/
```

och servera dem via controller efter behörighetskontroll.

Exempel:

```text
GET /files/{id}
  -> auth middleware
  -> controller kontrollerar behörighet
  -> streamar fil
```

Se mer i:

- [`FILES.md`](FILES.md)
- [`SECURITY.md`](SECURITY.md)

---

## Radera gamla filer

När en användare byter avatar bör du normalt radera den gamla filen om den inte är default-avatar.

Exempelprincip:

```php
$oldAvatar = $user->avatar;

$user->avatar = $newAvatar;
$user->save();

if ($oldAvatar !== null && $oldAvatar !== '/assets/images/graphics/avatar.png') {
    // radera gammal fil om den ligger under /uploads
}
```

Var försiktig så att du inte råkar radera assets eller filer utanför uploads.

---

## Visa bilder i templates

Exempel:

```html
<img src="{{ versioned_file($user->getAttribute('avatar'), '/assets/images/graphics/avatar.png') }}" alt="Avatar">
```

För statiska bilder:

```html
<img src="{{ versioned_file('/assets/images/graphics/logo.png') }}" alt="Logo">
```

---

## Cache av bilder

För app-assets kan du använda lång cache eftersom `versioned_file()` hjälper till vid ändringar.

För uploads bör du tänka på:

- om filnamn byts vid varje ny upload
- om samma path skrivs över
- om webbläsare/CDN cachear gamla bilder

Rekommenderat:

```text
ny upload = nytt filnamn
```

Det gör cache enklare.

---

## Bildkvalitet

När du sparar JPEG/WebP kan kvalitet påverka filstorlek.

Exempel:

```php
$image->saveImage($targetPath, quality: 80);
```

Rekommenderat:

```text
70-85 för vanliga webbilder
80-90 för högre kvalitet
```

Testa visuellt och mät filstorlek.

---

## EXIF och rotation

Vissa bilder från mobiler kan innehålla EXIF-orientation.

Om appen behöver korrekt orientering bör upload/image-service kontrollera EXIF och rotera bilden vid behov.

Det kräver PHP-extension:

```text
ext-exif
```

Radix App kräver normalt `ext-exif` via Composer om bildflödet använder det.

---

## GD extension

Bildbehandling kräver GD:

```text
ext-gd
```

Kontrollera att extension är installerad i servermiljön.

---

## Vanliga flöden

### Avatar upload

```text
validera fil
spara/processa bild
cropa till 200x200
spara publik path i databasen
radera gammal avatar om relevant
```

### Produktbild

```text
validera fil
skala till maxbredd
skapa eventuell thumbnail
spara path i databasen
```

### Privat dokument

```text
validera fil
spara utanför public/
spara metadata i databasen
servera via controller med auth
```

---

## Felsökning

### Upload directory kan inte skapas

Kontrollera filrättigheter på:

```text
public/uploads/
```

eller den katalog du skickar till `Upload`.

### `Filen är inte en giltig uppladdning`

Kontrollera:

- att filen kommer från `$_FILES`
- att `error` är `UPLOAD_ERR_OK`
- att formuläret har `enctype="multipart/form-data"`
- att PHP:s `upload_max_filesize` och `post_max_size` tillåter storleken

Formulär ska ha:

```html
<form method="post" enctype="multipart/form-data">
```

### `Otillåten filtyp`

Kontrollera MIME-typ.

Endast vissa MIME-typer mappas automatiskt till extension vid genererat filnamn:

```text
image/jpeg
image/png
image/gif
image/webp
```

### Bildbehandling fungerar inte

Kontrollera att GD är installerat:

```bash
php -m
```

Leta efter:

```text
gd
```

### Stor bild ger memory error

Minska max filstorlek och bilddimensioner.

Kontrollera PHP memory limit.

### Bild visas inte

Kontrollera:

- att filen finns under `public/`
- att publik path börjar med `/uploads/` eller `/assets/`
- att `.htaccess` inte blockerar filtypen
- att webbläsaren inte cachear gammal path

---

## Bra praxis

- använd `public/assets` för appens egna bilder
- använd `public/uploads` för användargenererade bilder
- spara publik path i databasen
- generera säkra filnamn
- validera MIME och storlek
- använd bildbehandling för att minska dimensioner
- skapa thumbnails där det behövs
- radera gamla filer säkert
- använd ny filpath vid ny upload för enklare cache
- tillåt inte SVG/HTML/JS som användarupload utan extra skydd
- lagra privata filer utanför webroot

---

## Relaterat

- [`FILES.md`](FILES.md)
- [`VALIDATION.md`](VALIDATION.md)
- [`FRONTEND.md`](FRONTEND.md)
- [`TEMPLATES.md`](TEMPLATES.md)
- [`SECURITY.md`](SECURITY.md)
- [`CONFIG.md`](CONFIG.md)
