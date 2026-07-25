<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrderChecklistItem;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkOrderChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
        ]);

        if ($this->has('position')) {
            $this->merge([
                'position' => (int) $this->input('position'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }
}
