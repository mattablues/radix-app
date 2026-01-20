# Bilder och Uppladdning

Radix förenklar hanteringen av bilder och filuppladdningar genom klasserna `Radix\File\Upload` och `Radix\File\Image`, samt genom den smidiga tjänsten `App\Services\UploadService`.

## Uppladdning (Upload)

Klassen `Radix\File\Upload` hanterar säker flytt av filer från temporära mappar till din lagringsplats. Den skapar automatiskt mappar och genererar unika filnamn.

### Grundläggande exempel
```php
$file = $request->files['avatar'];
$upload = new Upload($file, ROOT_PATH . '/public/uploads/');

if ($upload->validate(['avatar' => 'file_type:image/jpeg|file_size:2'])) {
    $path = $upload->save(); // Returnerar fullständig sökväg till den sparade filen
}
```

## Bildbehandling (Image)

`Image`-klassen är en wrapper runt PHP GD och gör det enkelt att manipulera bilder (resizing, cropping, vattenstämplar).

```php
$image = new Image('path/to/image.jpg');

// Ändra storlek (auto, portrait, landscape, exact)
$image->resizeImage(800, 600, 'auto');

// Beskär bilden till exakta mått (centrerat)
$image->resizeImage(200, 200, 'crop');

// Applicera filter eller rotation
$image->rotateImage(90);
$image->applyGrayscale();

// Spara den bearbetade bilden
$image->saveImage('path/to/new_image.jpg', quality: 80);
```

## UploadService (Rekommenderat flöde)

För att hålla dina Controllers rena bör du använda `UploadService`. Den kombinerar uppladdning och bildbehandling i ett svep.

### Använda fördefinierade profiler
```php
$service = new UploadService();

// Laddar upp, beskär till 200x200 och sparar som avatar.jpg
$url = $service->uploadAvatar($request->files['avatar'], $directory);

// Laddar upp och skalar till produktbild (600x600)
$url = $service->uploadProductImage($request->files['photo'], $directory);
```

### Anpassad bearbetning
Du kan skicka med en callback för att skräddarsy bearbetningen:
```php
$service->uploadImage($file, $dir, function(Image $image) {
    $image->resizeImage(400, 300);
    $image->addWatermark('logo.png', x: 10, y: 10);
});
```

## Säkerhetstips

1.  **Validering**: Använd alltid `file_type` och `file_size` regler i din validator.
2.  **Lagring**: Spara filer utanför webbroten om de inte ska vara publika, eller använd unika ID-mappar (t.ex. `/public/images/user/{id}/`).
3.  **Filnamn**: Radix genererar automatiskt säkra `uniqid()`-baserade filnamn för att förhindra överskrivning och skadliga filnamn.