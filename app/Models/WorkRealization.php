<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'work_date', 'shift_id', 'batch_id', 'product_id', 'sku_snapshot', 'product_name_snapshot', 'unit_name_snapshot', 'total_output', 'start_time', 'end_time', 'report', 'result_image_path', 'is_complaint', 'status', 'created_by', 'finalized_by', 'finalized_at'])]
class WorkRealization extends Model
{
    use BelongsToClient;

    protected function casts(): array
    {
        return ['work_date' => 'date', 'total_output' => 'decimal:3', 'is_complaint' => 'boolean', 'finalized_at' => 'datetime'];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(RealizationEmployee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
