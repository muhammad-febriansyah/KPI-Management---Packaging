<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'user_id',
    'employee_no',
    'sim_id',
    'full_name',
    'email',
    'phone',
    'join_date',
    'gender',
    'employee_status',
    'marital_status',
    'group_id',
    'rate_category',
    'status',
])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use BelongsToClient, HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
