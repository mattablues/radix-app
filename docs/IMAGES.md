# docs/IMAGES.md

← [`Tillbaka till index`](INDEX.md)

# Bilder & uppladdningar (Radix App)

Radix förenklar hanteringen av filer och bilder via:

- `Radix\File\Upload` (säker uppladdning/flytt/validering)
- `Radix\File\Image` (bildbehandling via GD)
- (valfritt) en app-service, t.ex. `App\Services\UploadService`, för att hålla controllers rena

---

## Uppladdning (Upload)

`Radix\File\Upload` hanterar säker flytt av filer från temporära mappar till din lagringsplats.
Den skapar mappar vid behov och kan generera unika filnamn.

### Grundexempel

```php
<?php

use Radix\File\Upload;

/** @var array<string,mixed> $files */
$files = $request->files;

/** @var mixed $file */
$file = $files['avatar'] ?? null;

$upload = new Upload($file, ROOT_PATH . '/public/uploads/');

if ($upload->validate(['avatar' => 'file_type:image/jpeg|file_size:2'])) {
    $path = $upload->save(); // returnerar sökvägen till sparad fil
}
```

---

## Bildbehandling (Image)

`Radix\File\Image` är en wrapper runt PHP GD och gör det enkelt att manipulera bilder (resize/crop/rotation/filter).

```php
<?php

use Radix\File\Image;

$image = new Image('path/to/image.jpg');

// Ändra storlek (auto, portrait, landscape, exact)
$image->resizeImage(800, 600, 'auto');

// Beskär till exakta mått (centrerat)
$image->resizeImage(200, 200, 'crop');

// Rotation och filter
$image->rotateImage(90);
$image->applyGrayscale();

// Spara
$image->saveImage('path/to/new_image.jpg', quality: 80);
```

---

## UploadService (rekommenderat flöde)

För att hålla controllers tunna är det ofta bäst att lägga uppladdningslogik i en service (t.ex. `App\Services\UploadService`) som:

- validerar filen
- laddar upp filen
- (valfritt) kör bildbehandling
- returnerar URL/path

### Exempel: fördefinierade “profiler”

```php
<?php

$service = new \App\Services\UploadService();

// Avatar: ladda upp + cropa till 200x200
$url = $service->uploadAvatar($request->files['avatar'], $directory);

// Produktbild: ladda upp + skala
$url = $service->uploadProductImage($request->files['photo'], $directory);
```

### Exempel: anpassad bearbetning med callback

```php
<?php

use Radix\File\Image;

$service->uploadImage($file, $dir, function (Image $image): void {
    $image->resizeImage(400, 300);
    $image->addWatermark('logo.png', x: 10, y: 10);
});
```

---

## Säkerhetstips

1) **Validera alltid** med `file_type` och `file_size` (se `docs/VALIDATION.md`)
2) **Lagra smart**
   - publika bilder: under `public/`
   - privata filer: utanför webbroten eller bakom auth
3) **Filnamn**
   - undvik att lita på originalnamn från användaren
   - använd unika, säkra namn (t.ex. genererade)
