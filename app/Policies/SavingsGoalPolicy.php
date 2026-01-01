<?php

namespace App\Policies;

use App\Models\SavingsGoal;
use App\Models\User;

class SavingsGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SavingsGoal $goal): bool
    {
        return $user->accounts->contains($goal->account_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SavingsGoal $goal): bool
    {
        if (!$user->accounts->contains($goal->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $goal->account_id)
            ->first()
            ->pivot;

        $isAccountManager = in_array($pivot->role, ['owner', 'admin'], true);

        if ($goal->user_id && $goal->user_id !== $user->id && !$isAccountManager) {
            return false;
        }

        return in_array($pivot->role, ['owner', 'admin', 'member']) && $pivot->is_active;
    }

    public function delete(User $user, SavingsGoal $goal): bool
    {
        return $this->update($user, $goal);
    }
}
