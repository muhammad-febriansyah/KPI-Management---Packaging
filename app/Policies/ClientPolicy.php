<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->status !== 'active') {
            return false;
        }

        return $user->is_super_admin || $user->clients()
            ->where('clients.status', 'active')
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return $this->select($user, $client);
    }

    public function select(User $user, Client $client): bool
    {
        if ($user->status !== 'active' || $client->status !== 'active') {
            return false;
        }

        return $user->is_super_admin || $user->clients()
            ->whereKey($client->getKey())
            ->wherePivot('status', 'active')
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    private function isActiveSuperAdmin(User $user): bool
    {
        return $user->status === 'active' && $user->is_super_admin;
    }
}
