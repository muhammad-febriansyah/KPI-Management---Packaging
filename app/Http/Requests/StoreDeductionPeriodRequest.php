<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesDeductionAmounts;
use App\Models\DeductionPeriod;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeductionPeriodRequest extends FormRequest
{
    use NormalizesDeductionAmounts;

    public function authorize(): bool
    {
        return $this->user()?->status === 'active' && app(CurrentClientService::class)->isResolved();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $clientId = app(CurrentClientService::class)->id();

        return [
            'month' => ['required', 'date_format:Y-m'],
            'week_no' => ['nullable', 'integer', 'between:1,2'],
            'uniform_amount' => ['nullable', 'numeric', 'min:0'],
            'equipment_amount' => ['nullable', 'numeric', 'min:0'],
            'meal_amount' => ['nullable', 'numeric', 'min:0'],
            'bpjs_health_percent' => ['nullable', 'numeric', 'between:0,100'],
            'bpjs_employment_percent' => ['nullable', 'numeric', 'between:0,100'],
            'salary_advance_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'salary_advance_value' => ['nullable', 'numeric', 'min:0', Rule::when($this->input('salary_advance_type') === 'percentage', ['max:100'])],
            'correction_minus' => ['nullable', 'numeric', 'min:0'],
            'correction_plus' => ['nullable', 'numeric', 'min:0'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', Rule::exists('employees', 'id')->where(fn ($q) => $q->where('client_id', $clientId))],
        ];
    }

    /**
     * Prevent duplicate periods before the database unique index rejects them.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('month') || $validator->errors()->has('week_no')) {
                return;
            }

            $month = (string) $this->input('month');
            $weekNo = $this->filled('week_no') ? (int) $this->input('week_no') : null;
            $periodExists = DeductionPeriod::query()
                ->where('client_id', app(CurrentClientService::class)->id())
                ->where('month', $month.'-01')
                ->where('week_key', $weekNo ?? 0)
                ->exists();

            if (! $periodExists) {
                return;
            }

            $periodLabel = $weekNo === null ? 'Semua minggu' : "Minggu {$weekNo}";
            $validator->errors()->add('month', "Periode {$month} ({$periodLabel}) sudah tersedia. Pilih periode lain.");
        }];
    }
}
