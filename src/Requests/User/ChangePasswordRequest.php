<?php

declare(strict_types=1);

namespace App\Requests\User;

use Radix\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required',
            'password' => 'required|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
        ];
    }

    public function currentPassword(): string
    {
        $value = $this->data['current_password'] ?? '';
        return is_string($value) ? $value : '';
    }

    public function password(): string
    {
        $value = $this->data['password'] ?? '';
        return is_string($value) ? $value : '';
    }
}
