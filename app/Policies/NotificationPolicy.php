<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Notification;

class NotificationPolicy
{
    /**
     * Determine if the user can view any notifications
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view notifications
        return true;
    }

    /**
     * Determine if the user can view a specific notification
     */
    public function view(User $user, Notification $notification): bool
    {
        // Check if notification is for user's role or for everyone
        if ($notification->target_role === 'all') {
            return true;
        }
        
        return $user->role === $notification->target_role || $user->isAdmin();
    }

    /**
     * Determine if the user can create a notification
     */
    public function create(User $user): bool
    {
        // Only admins can create notifications
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update a notification
     */
    public function update(User $user, Notification $notification): bool
    {
        // Only admins can update notifications
        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete a notification
     */
    public function delete(User $user, Notification $notification): bool
    {
        // Only admins can delete notifications
        return $user->isAdmin();
    }

    /**
     * Determine if the user can restore a notification
     */
    public function restore(User $user, Notification $notification): bool
    {
        // Only admins can restore notifications
        return $user->isAdmin();
    }

    /**
     * Determine if the user can permanently delete a notification
     */
    public function forceDelete(User $user, Notification $notification): bool
    {
        // Only admins can force delete
        return $user->isAdmin();
    }
}
