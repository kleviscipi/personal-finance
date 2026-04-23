<?php

namespace App\Policies;

use App\Models\RecurringTransaction;
use App\Models\User;

class RecurringTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecurringTransaction $recurringTransaction): bool
    {
        return $user->accounts->contains($recurringTransaction->account_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RecurringTransaction $recurringTransaction): bool
    {
        if (!$user->accounts->contains($recurringTransaction->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $recurringTransaction->account_id)
            ->first()
            ?->pivot;

        return $pivot && in_array($pivot->role, ['owner', 'admin', 'member'], true) && $pivot->is_active;
    }

    public function delete(User $user, RecurringTransaction $recurringTransaction): bool
    {
        return $this->update($user, $recurringTransaction);
    }
}
