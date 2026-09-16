<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesDeductionAmounts;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeDeductionRequest extends FormRequest
{
    use NormalizesDeductionAmounts;

    public function authorize(): bool
    {
        return $this->user()?->status === 'active' && app(CurrentClientService::class)->isResolved();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'uniform_amount' => ['nullable', 'numeric', 'min:0'],
            'equipment_amount' => ['nullable', 'numeric', 'min:0'],
            'meal_amount' => ['nullable', 'numeric', 'min:0'],
            'bpjs_health_percent' => ['nullable', 'numeric', 'between:0,100'],
            'bpjs_employment_percent' => ['nullable', 'numeric', 'between:0,100'],
            'salary_advance_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'salary_advance_value' => ['nullable', 'numeric', 'min:0', Rule::when($this->input('salary_advance_type') === 'percentage', ['max:100'])],
            'correction_minus' => ['nullable', 'numeric', 'min:0'],
            'correction_plus' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
