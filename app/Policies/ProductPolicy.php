<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Services\CurrentClientService;

class ProductPolicy
{
    public function __construct(private CurrentClientService $currentClient) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->status === 'active' && ($user->is_super_admin || $user->clients()->wherePivot('status', 'active')->exists());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->access($user, $product);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->access($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->access($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Records are reachable only through the client the user is currently switched into,
     * never through every client the user happens to belong to. Membership alone would let
     * a user assigned to two clients act on the other one's records without switching, and
     * would let a super admin reach every client at once.
     */
    private function access(User $user, Product $product): bool
    {
        return $this->viewAny($user)
            && $this->currentClient->isResolved()
            && $product->client_id === $this->currentClient->id();
    }
}
