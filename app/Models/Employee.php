<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClient;
use App\Models\Scopes\ClientScope;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'birth_date',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withoutGlobalScopes();
    }

    /**
     * Active employees with a login that a user may assign to a realization.
     * Super admins assign across every client; other users stay on the active client,
     * plus any employee already assigned to the realization being edited.
     *
     * @param  Builder<Employee>  $query
     * @param  array<int, int>  $alreadyAssignedIds
     * @return Builder<Employee>
     */
    #[Scope]
    protected function assignableBy(Builder $query, User $user, int $clientId, array $alreadyAssignedIds = []): Builder
    {
        return $query->withoutGlobalScope(ClientScope::class)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->when(! $user->is_super_admin, fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                ->where($query->qualifyColumn('client_id'), $clientId)
                ->orWhereIn($query->qualifyColumn('id'), $alreadyAssignedIds)));
    }
}
