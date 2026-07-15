<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'type', 'manufacturer', 'model', 'serial_number', 'notes'] as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => $this->filled($field) ? trim((string) $this->input($field)) : null,
                ]);
            }
        }

        if ($this->has('installed_at')) {
            $this->merge([
                'installed_at' => $this->filled('installed_at') ? $this->input('installed_at') : null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'manufacturer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installed_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
