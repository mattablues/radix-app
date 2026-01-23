<?php

declare(strict_types=1);

namespace App\Requests\Admin;

use Radix\Http\FormRequest;

final class SystemUpdateRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'version'     => 'required|min:1|max:20',
            'title'       => 'required|min:3|max:100',
            'description' => 'required|min:10',
            'released_at' => 'required',
        ];
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

    public function version(): string
    {
        $value = $this->data['version'] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function title(): string
    {
        $value = $this->data['title'] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public function description(): string
    {
        $value = $this->data['description'] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    /**
     * Returnerar released_at som "Y-m-d H:i:s" (om input är "Y-m-d" fyller vi på med tid).
     */
    public function releasedAt(): string
    {
        $raw = $this->data['released_at'] ?? '';
        $releasedAt = is_string($raw) ? trim($raw) : '';

        if ($releasedAt === '') {
            $releasedAt = date('Y-m-d');
        }

        if (strlen($releasedAt) === 10) {
            $releasedAt .= ' ' . date('H:i:s');
        }

        return $releasedAt;
    }

    public function isMajor(): int
    {
        return (isset($this->data['is_major']) && $this->data['is_major'] === '1') ? 1 : 0;
    }
}
