<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Models\WorkRealization;
use App\Services\CurrentClientService;

class WorkRealizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->is_super_admin || $user->clients()->wherePivot('status', 'active')->exists());
    }

    /**
     * Determine whether the user can view the model.
     * An employee sees realizations assigned to them or created by them; any
     * other role granted the "realizations" menu sees every client realization.
     */
    public function view(User $user, WorkRealization $workRealization): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $client = Client::query()->find($workRealization->client_id);

        if ($client && $user->roleCodeFor($client) === 'employee') {
            return $this->belongsToEmployee($user, $workRealization) || $workRealization->created_by === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $client = app(CurrentClientService::class);

        return $user->status === 'active'
            && ($user->is_super_admin || ($client->isResolved() && $user->roleCodeFor($client->get()) === 'employee'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkRealization $workRealization): bool
    {
        return $user->status === 'active'
            && ! $user->is_super_admin
            && ($this->belongsToEmployee($user, $workRealization) || $workRealization->created_by === $user->id);
    }

    /**
     * Determine whether the user can assign employees to the model.
     */
    public function assign(User $user, WorkRealization $workRealization): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $client = app(CurrentClientService::class);

        return $client->isResolved()
            && $workRealization->client_id === $client->id()
            && $user->roleCodeFor($client->get()) === 'employee'
            && $workRealization->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkRealization $workRealization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkRealization $workRealization): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkRealization $workRealization): bool
    {
        return false;
    }

    private function belongsToEmployee(User $user, WorkRealization $workRealization): bool
    {
        return $workRealization->employeeAssignments()
            ->whereHas('employee', fn ($query) => $query->where('user_id', $user->id)->where('client_id', $workRealization->client_id))
            ->exists();
    }
}
