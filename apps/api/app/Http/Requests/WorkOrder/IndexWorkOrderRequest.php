<?php

declare(strict_types=1);

namespace App\Http\Requests\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexWorkOrderRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(WorkOrder::STATUSES)],
            'priority' => ['nullable', Rule::in(WorkOrder::PRIORITIES)],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function searchQuery(): ?string
    {
        $query = $this->validated('q');

        return is_string($query) && $query !== '' ? $query : null;
    }

    public function status(): ?string
    {
        $status = $this->validated('status');

        return is_string($status) && $status !== '' ? $status : null;
    }

    public function priority(): ?string
    {
        $priority = $this->validated('priority');

        return is_string($priority) && $priority !== '' ? $priority : null;
    }

    public function perPage(): int
    {
        $perPage = $this->validated('per_page');

        return is_numeric($perPage) ? (int) $perPage : 10;
    }
}
