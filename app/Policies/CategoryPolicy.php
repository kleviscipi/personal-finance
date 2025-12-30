<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
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
    public function view(User $user, Category $category): bool
    {
        return $user->accounts->contains($category->account_id);
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
    public function update(User $user, Category $category): bool
    {
        if (!$user->accounts->contains($category->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $category->account_id)
            ->first()
            ->pivot;

        return in_array($pivot->role, ['owner', 'admin']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        // Cannot delete system categories
        if ($category->is_system) {
            return false;
        }

        return $this->update($user, $category);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        if (!$user->accounts->contains($category->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $category->account_id)
            ->first()
            ->pivot;

        return in_array($pivot->role, ['owner', 'admin']) && $pivot->is_active;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        // Cannot force delete system categories
        if ($category->is_system) {
            return false;
        }

        if (!$user->accounts->contains($category->account_id)) {
            return false;
        }

        $pivot = $user->accounts()
            ->where('account_id', $category->account_id)
            ->first()
            ->pivot;

        return $pivot->role === 'owner' && $pivot->is_active;
    }
}
