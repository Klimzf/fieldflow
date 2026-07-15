<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEquipmentRequest extends FormRequest
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
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'type' => $this->filled('type') ? trim((string) $this->input('type')) : null,
            'manufacturer' => $this->filled('manufacturer') ? trim((string) $this->input('manufacturer')) : null,
            'model' => $this->filled('model') ? trim((string) $this->input('model')) : null,
            'serial_number' => $this->filled('serial_number') ? trim((string) $this->input('serial_number')) : null,
            'installed_at' => $this->filled('installed_at') ? $this->input('installed_at') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'installed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
