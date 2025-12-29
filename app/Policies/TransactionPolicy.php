<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view transactions in their accounts
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // User must belong to the transaction's account
        return $user->accounts->contains($transaction->account_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Members and above can create transactions
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Must belong to account and not be viewer
        if (!$user->accounts->contains($transaction->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $transaction->account_id)
            ->first()
            ->pivot;

        return in_array($pivot->role, ['owner', 'admin', 'member']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Same as update - members and above can delete
        return $this->update($user, $transaction);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        // Admin and owner can restore
        if (!$user->accounts->contains($transaction->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $transaction->account_id)
            ->first()
            ->pivot;

        return in_array($pivot->role, ['owner', 'admin']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        // Only owner can force delete
        if (!$user->accounts->contains($transaction->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $transaction->account_id)
            ->first()
            ->pivot;

        return $pivot->role === 'owner' && $pivot->is_active;
    }
}
