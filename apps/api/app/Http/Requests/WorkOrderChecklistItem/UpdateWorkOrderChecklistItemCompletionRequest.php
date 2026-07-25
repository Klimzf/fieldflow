<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrderChecklistItem;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkOrderChecklistItemCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_completed' => ['required', 'boolean'],
        ];
    }
}
