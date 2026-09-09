<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'work_realization_id', 'employee_id', 'rate_category_snapshot', 'rate_per_unit_snapshot', 'allocation_output', 'gross_amount'])]
class RealizationEmployee extends Model
{
    use BelongsToClient;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'rate_per_unit_snapshot' => 'decimal:3',
            'allocation_output' => 'decimal:3',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
