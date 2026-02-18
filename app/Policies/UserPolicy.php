<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->is_active;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admins can view any user
        if ($user->isAdmin() && $user->is_active) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->is_active && $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can update other users
        if ($user->isAdmin() && $user->is_active && $user->hasPermission('manage_users')) {
            // Cannot edit users with higher privileges
            return $user->admin_type === User::ADMIN_TYPE_SUPER ||
                   $user->admin_type !== User::ADMIN_TYPE_OPERATOR;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Only super admins can delete
        return $user->isAdmin() &&
               $user->is_active &&
               $user->isSuperAdmin() &&
               $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->is_active;
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->is_active;
    }

    /**
     * Determine if user can manage admin accounts
     */
    public function manageAdmins(User $user): bool
    {
        return $user->isSuperAdmin() && $user->is_active && $user->hasPermission('manage_admins');
    }

    /**
     * Determine if user can accept terms
     */
    public function acceptTerms(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}
