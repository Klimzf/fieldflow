<?php

declare(strict_types=1);

namespace App\Http\Requests\Client;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            ]);
        }

        if ($this->has('phone')) {
            $this->merge([
                'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            ]);
        }

        if ($this->has('address')) {
            $this->merge([
                'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            ]);
        }

        if ($this->has('notes')) {
            $this->merge([
                'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
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
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
