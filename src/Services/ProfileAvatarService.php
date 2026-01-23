<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final readonly class ProfileAvatarService
{
    public function __construct(private UploadService $uploadService) {}

    /**
     * @param int|string $userId
     * @param array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null $avatar
     */
    public function updateAvatar(User $user, int|string $userId, ?array $avatar): void
    {
        if ($avatar === null || $avatar['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $uploadDirectory = ROOT_PATH . "/public/images/user/$userId/";

        if ($user->avatar !== '/images/graphics/avatar.png') {
            $oldAvatarPath = ROOT_PATH . $user->avatar;
            if (file_exists($oldAvatarPath)) {
                unlink($oldAvatarPath);
            }
        }

        $uploadedPath = $this->uploadService->uploadAvatar($avatar, $uploadDirectory);
        if ($uploadedPath !== '') {
            $user->avatar = $uploadedPath;
        }
    }
}
