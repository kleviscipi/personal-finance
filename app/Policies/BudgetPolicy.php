<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Budget $budget): bool
    {
        return $user->accounts->contains($budget->account_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Budget $budget): bool
    {
        if (!$user->accounts->contains($budget->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $budget->account_id)
            ->first()
            ->pivot;

        $isAccountManager = in_array($pivot->role, ['owner', 'admin']);

        if ($budget->user_id && $budget->user_id !== $user->id && !$isAccountManager) {
            return false;
        }

        return in_array($pivot->role, ['owner', 'admin', 'member']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Budget $budget): bool
    {
        return $this->update($user, $budget);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Budget $budget): bool
    {
        if (!$user->accounts->contains($budget->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $budget->account_id)
            ->first()
            ->pivot;

        return in_array($pivot->role, ['owner', 'admin']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Budget $budget): bool
    {
        if (!$user->accounts->contains($budget->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $budget->account_id)
            ->first()
            ->pivot;

        return $pivot->role === 'owner' && $pivot->is_active;
    }
}
