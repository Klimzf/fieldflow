<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkOrderRequest extends FormRequest
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
            'title' => trim((string) $this->input('title')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : 'new',
            'priority' => $this->filled('priority') ? trim((string) $this->input('priority')) : 'medium',
            'equipment_id' => $this->filled('equipment_id') ? (int) $this->input('equipment_id') : null,
            'scheduled_at' => $this->filled('scheduled_at') ? $this->input('scheduled_at') : null,
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
            'equipment_id' => ['nullable', 'integer', 'exists:equipment,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', Rule::in(['new', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
