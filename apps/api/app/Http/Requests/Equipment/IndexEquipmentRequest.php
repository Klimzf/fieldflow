<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class IndexEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function searchQuery(): ?string
    {
        $query = $this->validated('q');

        return is_string($query) && $query !== '' ? $query : null;
    }

    public function perPage(): int
    {
        $perPage = $this->validated('per_page');

        return is_numeric($perPage) ? (int) $perPage : 10;
    }
}
