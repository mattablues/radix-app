<?php

declare(strict_types=1);

namespace App\Services;
use Radix\File\Image;

class UploadService
{
    /**
     * Hanterar uppladdning och bearbetning av en bild
     */
    public function uploadImage(array $file, string $uploadDirectory, ?callable $processImageCallback = null, ?string $fileName = null): string
    {
        // Kontrollera om filen har laddats upp korrekt
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Fel vid uppladdning av filen.');
        }

        // Skapa uppladdningskatalog om den inte finns
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        // Hämta MIME-typ
        $mimeType = mime_content_type($file['tmp_name']);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new \RuntimeException("Bildformat \"$mimeType\" stöds inte."),
        };

        // Skapa filnamn om det inte är specificerat
        $fileName = $fileName ?? uniqid('image_', true) . '.' . $extension;

        // Fullständig filväg
        $filePath = $uploadDirectory . $fileName;

        // Flytta till uppladdningsmappen
        move_uploaded_file($file['tmp_name'], $filePath);

        // Bearbeta bilden om en callback skickas med
        if ($processImageCallback) {
            $image = new Image($filePath);
            $processImageCallback($image);
            $image->saveImage($filePath);
        }

        // Returnera relativ filväg
        return str_replace(ROOT_PATH . '/public', '', $filePath);
    }

    /**
     * Ladda upp och bearbeta en användaravatar
     */
    public function uploadAvatar(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(200, 200, 'crop'); // Beskär bilden för avatar
            },
            'avatar.jpg'
        );
    }

    /**
     * Ladda upp och bearbeta en bannerbild
     */
    public function uploadBanner(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(1200, 450, 'crop'); // Bannerstorlek
            },
            'banner_' . uniqid() . '.jpg'
        );
    }

    /**
     * Ladda upp och bearbeta en produktbild
     */
    public function uploadProductImage(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(600, 600); // Ändra storlek på produktbild
            },
            null
        );
    }
}