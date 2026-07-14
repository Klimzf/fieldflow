<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSiteRequest extends FormRequest
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
        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }

        if ($this->has('address')) {
            $this->merge([
                'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            ]);
        }

        if ($this->has('contact_name')) {
            $this->merge([
                'contact_name' => $this->filled('contact_name') ? trim((string) $this->input('contact_name')) : null,
            ]);
        }

        if ($this->has('contact_phone')) {
            $this->merge([
                'contact_phone' => $this->filled('contact_phone') ? trim((string) $this->input('contact_phone')) : null,
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
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
