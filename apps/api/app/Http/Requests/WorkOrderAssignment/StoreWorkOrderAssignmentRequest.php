<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrderAssignment;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkOrderAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('user_id')) {
            $this->merge([
                'user_id' => (int) $this->input('user_id'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
