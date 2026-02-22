<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type', // general | payment_due | payment_approved | payment_rejected
        'start_date',
        'end_date',
        'target_role', // student | accounting | admin | all
        'user_id', // Specific student (nullable)
        'is_active', // Whether notification is enabled
        'is_complete', // Auto-set when payment is complete
        'dismissed_at', // When user dismissed the notification
        'term_ids', // JSON array of specific term IDs to target
        'target_term_name', // Target by term name (e.g., "Upon Registration")
        'trigger_days_before_due', // Show N days before due date
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active' => 'boolean',
        'is_complete' => 'boolean',
        'dismissed_at' => 'datetime',
        'term_ids' => 'array', // Cast JSON to array
    ];

    /**
     * Relationship to the user this notification is targeted to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only active notifications
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('is_complete', false);
    }

    /**
     * Scope to get notifications for a specific user (by ID or email)
     */
    public function scopeForUser($query, int|string $userIdentifier)
    {
        // If it's an email, find the user first
        if (is_string($userIdentifier) && str_contains($userIdentifier, '@')) {
            $user = User::where('email', $userIdentifier)->first();
            return $query->where(function ($q) use ($user) {
                // Get both targeted notifications and role-based notifications
                if ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere(function ($q2) use ($user) {
                          $q2->where('target_role', $user->role)
                             ->orWhere('target_role', 'all')
                             ->whereNull('user_id');
                      });
                } else {
                    $q->where('user_id', null)
                      ->whereNull('target_role');
                }
            });
        }

        // If it's a user ID
        $user = User::find($userIdentifier);
        return $query->where(function ($q) use ($user, $userIdentifier) {
            // Get both targeted notifications and role-based notifications
            $q->where('user_id', $userIdentifier)
              ->orWhere(function ($q2) use ($user) {
                  $q2->whereNull('user_id')
                     ->where(function ($q3) use ($user) {
                         $q3->where('target_role', $user->role ?? 'student')
                            ->orWhere('target_role', 'all');
                     });
              });
        });
    }

    /**
     * Scope to get notifications within the active date range
     */
    public function scopeWithinDateRange($query)
    {
        $now = now()->toDateString();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_date')
              ->orWhere('start_date', '<=', $now)
              ->where(function ($q2) use ($now) {
                  $q2->whereNull('end_date')
                     ->orWhere('end_date', '>=', $now);
              });
        });
    }

    /**
     * Check if notification is currently active and within date range
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active 
            && !$this->is_complete 
            && !$this->dismissed_at
            && (!$this->start_date || $this->start_date <= now()->toDateString())
            && (!$this->end_date || $this->end_date >= now()->toDateString());
    }

    /**
     * Mark notification as complete (e.g., when payment is done)
     */
    public function markComplete(): void
    {
        $this->update(['is_complete' => true]);
    }

    /**
     * Mark notification as dismissed by user
     */
    public function markDismissed(): void
    {
        $this->update(['dismissed_at' => now()]);
    }
}