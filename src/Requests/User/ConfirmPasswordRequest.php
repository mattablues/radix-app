<?php

declare(strict_types=1);

namespace App\Requests\User;

use Radix\Http\FormRequest;

class ConfirmPasswordRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required',
        ];
    }

    public function currentPassword(): string
    {
        $value = $this->data['current_password'] ?? '';
        return is_string($value) ? $value : '';
    }
}
