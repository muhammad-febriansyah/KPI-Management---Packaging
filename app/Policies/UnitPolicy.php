<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Services\CurrentClientService;

class UnitPolicy
{
    public function __construct(private CurrentClientService $currentClient) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->is_super_admin || $user->clients()->wherePivot('status', 'active')->exists());
    }

    public function view(User $user, Unit $unit): bool
    {
        return $this->canAccessUnit($user, $unit);
    }

    public function create(User $user): bool
    {
        return $user->status === 'active' && ($user->is_super_admin || $user->clients()->wherePivot('status', 'active')->exists());
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->canAccessUnit($user, $unit);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->canAccessUnit($user, $unit);
    }

    /**
     * Records are reachable only through the client the user is currently switched into,
     * never through every client the user happens to belong to. Membership alone would let
     * a user assigned to two clients act on the other one's records without switching, and
     * would let a super admin reach every client at once.
     */
    private function canAccessUnit(User $user, Unit $unit): bool
    {
        return $this->viewAny($user)
            && $this->currentClient->isResolved()
            && $unit->client_id === $this->currentClient->id();
    }
}
