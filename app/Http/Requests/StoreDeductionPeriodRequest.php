<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesDeductionAmounts;
use App\Models\Client;
use App\Models\DeductionPeriod;
use App\Models\EmployeeDeduction;
use App\Services\CurrentClientService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeductionPeriodRequest extends FormRequest
{
    use NormalizesDeductionAmounts {
        prepareForValidation as normalizeDeductionAmounts;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDeductionAmounts();

        if (! $this->filled('client_id') && app(CurrentClientService::class)->isResolved()) {
            $this->merge(['client_id' => app(CurrentClientService::class)->id()]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->status === 'active' && app(CurrentClientService::class)->isResolved();
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $client = app(CurrentClientService::class);
        $clientId = $this->integer('client_id') ?: $client->id();
        $allowedClientIds = $this->user()?->is_super_admin
            ? $client->availableFor($this->user())->modelKeys()
            : [$client->id()];

        return [
            'client_id' => ['required', 'integer', Rule::in($allowedClientIds)],
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
            'employee_ids.*' => ['integer', 'distinct', Rule::exists('employees', 'id')->where(fn ($q) => $q->where('client_id', $clientId)->where('status', 'active'))],
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
            if ($validator->errors()->hasAny(['client_id', 'month', 'week_no', 'employee_ids'])) {
                return;
            }

            $clientId = $this->integer('client_id');
            $targetClient = Client::query()->active()->find($clientId);
            if (! $targetClient) {
                return;
            }

            $month = (string) $this->input('month');
            $weekNo = $this->filled('week_no') ? (int) $this->input('week_no') : null;
            $employeeIds = array_values(array_filter((array) $this->input('employee_ids')));
            if ($employeeIds === []) {
                return;
            }

            $existingEmployeeIds = app(CurrentClientService::class)->runAs($targetClient, function () use ($clientId, $month, $weekNo, $employeeIds): array {
                $period = DeductionPeriod::query()
                    ->where('client_id', $clientId)
                    ->where('month', $month.'-01')
                    ->where('week_key', $weekNo ?? 0)
                    ->first();

                if (! $period) {
                    return [];
                }

                return EmployeeDeduction::query()
                    ->where('deduction_period_id', $period->getKey())
                    ->whereIn('employee_id', $employeeIds)
                    ->pluck('employee_id')
                    ->all();
            });

            if ($existingEmployeeIds !== []) {
                $validator->errors()->add('employee_ids', 'Karyawan terpilih sudah memiliki potongan pada kombinasi periode bulan, minggu, dan client tersebut.');
            }
        }];
    }
}
