<?php

declare(strict_types=1);

namespace App\Requests\User;

use Radix\Http\FormRequest;
use Radix\Support\Validator;

class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        // email-unikhet hanteras separat (behöver userId)
        return [
            'first_name' => 'required|min:2|max:15',
            'last_name'  => 'required|min:2|max:15',
            'email'      => 'required|email',
        ];
    }

    public function firstName(): string
    {
        $value = $this->data['first_name'] ?? '';
        return is_string($value) ? $value : '';
    }

    public function lastName(): string
    {
        $value = $this->data['last_name'] ?? '';
        return is_string($value) ? $value : '';
    }

    public function email(): string
    {
        $value = $this->data['email'] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return strtolower(trim($value));
    }

    /**
     * Extra validering: email unik med undantag för current user.
     *
     * @param int|string $userId
     * @return array<string, array<int, string>>
     */
    public function validateUniqueEmailForUserId(int|string $userId): array
    {
        $validator = new Validator($this->request->post, [
            'email' => 'required|email|unique:App\Models\User,email,id=' . $userId,
        ]);

        if ($validator->validate()) {
            return [];
        }

        return $validator->errors();
    }

    /**
     * Extra validering: avatar (fil) regler.
     *
     * @param array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null $avatar
     * @return array<string, array<int, string>>
     */
    public function validateAvatar(?array $avatar): array
    {
        $validator = new Validator(['avatar' => $avatar], [
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
        ]);

        if ($validator->validate()) {
            return [];
        }

        return $validator->errors();
    }

    /**
     * @return array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null
     */
    public function avatar(): ?array
    {
        $rawAvatar = $this->request->files['avatar'] ?? null;

        if (!is_array($rawAvatar) || !array_key_exists('error', $rawAvatar)) {
            return null;
        }

        /** @var array{error:int,name?:string,tmp_name?:string,size?:int,type?:string} $rawAvatar */
        return $rawAvatar;
    }

    /**
     * Samlar alla extra valideringar för update() i en klump.
     *
     * @param int|string $userId
     * @param array{error:int,name?:string,tmp_name?:string,size?:int,type?:string}|null $avatar
     * @return array<string, array<int, string>>
     */
    public function extraErrorsForUpdate(int|string $userId, ?array $avatar): array
    {
        $errors = $this->validateUniqueEmailForUserId($userId);

        foreach ($this->validateAvatar($avatar) as $field => $messages) {
            foreach ($messages as $msg) {
                $errors[$field][] = $msg;
            }
        }

        return $errors;
    }
}
