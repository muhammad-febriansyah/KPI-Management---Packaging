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
     * An employee only sees their own assignments; any other role that has
     * been granted the "realizations" menu (checked at the controller level)
     * sees every realization for their client.
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
            return $this->belongsToEmployee($user, $workRealization);
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
            && $workRealization->status === 'assigned'
            && $this->belongsToEmployee($user, $workRealization);
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
