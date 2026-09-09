<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view the role & menu access list.
     */
    public function viewAny(User $user): bool
    {
        return $this->isActiveSuperAdmin($user);
    }

    /**
     * Determine whether the user can change which menus a role can see.
     * The Super Admin role itself is never editable — it always has full access.
     */
    public function update(User $user, Role $role): bool
    {
        return $this->isActiveSuperAdmin($user) && $role->code !== 'super-admin';
    }

    private function isActiveSuperAdmin(User $user): bool
    {
        return $user->status === 'active' && $user->is_super_admin;
    }
}
