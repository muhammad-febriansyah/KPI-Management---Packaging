<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'month', 'week_no', 'status', 'created_by', 'locked_by', 'locked_at'])]
class DeductionPeriod extends Model
{
    use BelongsToClient;

    protected function casts(): array
    {
        return ['month' => 'date', 'locked_at' => 'datetime'];
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }
}
