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

    /**
     * Lägg till honeypot-regel om den finns i sessionen.
     *
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, string|array<int, string>>
     */
    protected function addExtraRules(array $rules): array
    {
        $honeypotId = $this->request->session()->get('honeypot_id');
        if (is_string($honeypotId) && $honeypotId !== '') {
            $rules[$honeypotId] = 'honeypot';
        }

        return $rules;
    }

    /**
     * Konvertera honeypot-fel till ett generiskt form-error.
     */
    protected function handleValidationErrors(): void
    {
        $honeypotId = $this->request->session()->get('honeypot_id');

        if (!is_string($honeypotId) || $honeypotId === '') {
            return;
        }

        $honeypotErrors = preg_grep('/^hp_/', array_keys($this->validator->errors()));

        if (!empty($honeypotErrors)) {
            $this->validator->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
        }
    }
}
