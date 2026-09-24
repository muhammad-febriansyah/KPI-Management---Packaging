<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'sku', 'name', 'unit_id', 'group_id', 'cost_center_id', 'po_price', 'employee_rate', 'estimated_output_per_hour', 'status'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToClient, HasFactory;

    protected function casts(): array
    {
        return [
            'po_price' => 'decimal:3',
            'employee_rate' => 'decimal:3',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }
}
