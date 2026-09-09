<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'deduction_period_id', 'employee_id', 'uniform_amount', 'equipment_amount', 'meal_amount', 'bpjs_health_percent', 'bpjs_employment_percent', 'salary_advance_type', 'salary_advance_value', 'correction_minus', 'correction_plus', 'notes'])]
class EmployeeDeduction extends Model
{
    use BelongsToClient;

    protected function casts(): array
    {
        return ['bpjs_health_percent' => 'decimal:3', 'bpjs_employment_percent' => 'decimal:3', 'salary_advance_value' => 'decimal:3'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionPeriod(): BelongsTo
    {
        return $this->belongsTo(DeductionPeriod::class);
    }
}
