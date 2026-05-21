<?php

declare(strict_types=1);

namespace App\Services;

use Radix\File\Image;
use Radix\File\Upload;

class UploadService
{
    /**
     * Hanterar uppladdning och bearbetning av en bild
     *
     * @param array<string, mixed> $file
     */
    public function uploadImage(array $file, string $uploadDirectory, ?callable $processImageCallback = null, ?string $fileName = null): string
    {
        $upload = new Upload($file, $uploadDirectory);

        $filePath = $processImageCallback !== null
            ? $upload->processImage($processImageCallback, $fileName ?? '')
            : $upload->save($fileName ?? '');

        // Returnera relativ filväg
        return str_replace(ROOT_PATH . '/public', '', $filePath);
    }

    /**
     * Ladda upp och bearbeta en användaravatar
     *
     * @param array<string, mixed> $file
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
     * Hanterar uppladdning och bearbetning av en bild
     *
     * @param array<string, mixed> $file
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
     *
     * @param array<string, mixed> $file
     */
    public function uploadProductImage(array $file, string $uploadDirectory): string
    {
        return $this->uploadImage(
            $file,
            $uploadDirectory,
            function (Image $image) {
                $image->resizeImage(600, 600); // Ändra storlek på produktbild
            }
        );
    }
}
