<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['client_id', 'code', 'name', 'status'])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToClient, HasFactory;

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
