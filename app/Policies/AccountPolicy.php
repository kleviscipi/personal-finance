<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view their own accounts
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Account $account): bool
    {
        // User must belong to the account
        return $user->accounts->contains($account->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create an account
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Account $account): bool
    {
        // Must be owner or admin
        return $this->hasRole($user, $account, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account): bool
    {
        // Only owner can delete account
        return $this->hasRole($user, $account, ['owner']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        // Only owner can restore account
        return $this->hasRole($user, $account, ['owner']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        // Only owner can force delete
        return $this->hasRole($user, $account, ['owner']);
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $user, Account $account): bool
    {
        // Owner and admin can manage members
        return $this->hasRole($user, $account, ['owner', 'admin']);
    }

    /**
     * Check if user has specific role in account
     */
    private function hasRole(User $user, Account $account, array $roles): bool
    {
        $pivot = $user->accounts()
            ->where('account_id', $account->id)
            ->first();

        if (!$pivot) {
            return false;
        }

        return in_array($pivot->pivot->role, $roles) && $pivot->pivot->is_active;
    }
}
