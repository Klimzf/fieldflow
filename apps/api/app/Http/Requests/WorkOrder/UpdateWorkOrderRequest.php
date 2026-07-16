<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateWorkOrderRequest extends FormRequest
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
        foreach (['title', 'description', 'status', 'priority'] as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => $this->filled($field) ? trim((string) $this->input($field)) : null,
                ]);
            }
        }

        if ($this->has('equipment_id')) {
            $this->merge([
                'equipment_id' => $this->filled('equipment_id') ? (int) $this->input('equipment_id') : null,
            ]);
        }

        if ($this->has('scheduled_at')) {
            $this->merge([
                'scheduled_at' => $this->filled('scheduled_at') ? $this->input('scheduled_at') : null,
            ]);
        }

        if ($this->has('completed_at')) {
            $this->merge([
                'completed_at' => $this->filled('completed_at') ? $this->input('completed_at') : null,
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
            'equipment_id' => ['sometimes', 'nullable', 'integer', 'exists:equipment,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['new', 'in_progress', 'completed', 'cancelled'])],
            'priority' => ['sometimes', 'required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
