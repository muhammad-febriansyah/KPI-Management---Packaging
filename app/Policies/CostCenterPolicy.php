<?php

namespace App\Policies;

use App\Models\CostCenter;
use App\Models\User;
use App\Services\CurrentClientService;

class CostCenterPolicy
{
    public function __construct(private CurrentClientService $currentClient) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->is_super_admin || $user->clients()->wherePivot('status', 'active')->exists());
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        return $this->access($user, $costCenter);
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        return $this->access($user, $costCenter);
    }

    /**
     * Records are reachable only through the client the user is currently switched into,
     * never through every client the user happens to belong to. Membership alone would let
     * a user assigned to two clients act on the other one's records without switching, and
     * would let a super admin reach every client at once.
     */
    private function access(User $user, CostCenter $costCenter): bool
    {
        return $this->viewAny($user)
            && $this->currentClient->isResolved()
            && $costCenter->client_id === $this->currentClient->id();
    }
}
