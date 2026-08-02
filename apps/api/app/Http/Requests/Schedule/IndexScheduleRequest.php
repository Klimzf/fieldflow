<?php

declare(strict_types=1);

namespace App\Http\Requests\Schedule;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class IndexScheduleRequest extends FormRequest
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
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }

    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->validated('start'),
        )->startOfDay();
    }

    public function endDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            (string) $this->validated('end'),
        )->endOfDay();
    }
}
