<?php

namespace App\Http\Requests\Concerns;

/**
 * Every deduction amount column is NOT NULL with a default of 0, but the form submits an
 * empty string for any amount the user leaves blank and the rules accept it as null. Writing
 * that null reaches the database as an integrity constraint violation, so blank amounts are
 * normalised to 0 here — the same value the column would have defaulted to.
 *
 * salary_advance_type is deliberately left alone: "Tidak ada" really is null.
 */
trait NormalizesDeductionAmounts
{
    /**
     * @var list<string>
     */
    private array $deductionAmountFields = [
        'uniform_amount',
        'equipment_amount',
        'meal_amount',
        'bpjs_health_percent',
        'bpjs_employment_percent',
        'salary_advance_value',
        'correction_minus',
        'correction_plus',
    ];

    protected function prepareForValidation(): void
    {
        $normalised = [];

        foreach ($this->deductionAmountFields as $field) {
            if ($this->has($field) && blank($this->input($field))) {
                $normalised[$field] = 0;
            }
        }

        if ($normalised !== []) {
            $this->merge($normalised);
        }
    }
}
