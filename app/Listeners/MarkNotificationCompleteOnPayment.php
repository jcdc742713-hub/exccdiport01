<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class MarkNotificationCompleteOnPayment implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentRecorded $event): void
    {
        $user = $event->user;
        $studentAssessment = $user->assessments()->latest('created_at')->first();

        // Check if student has fully paid their assessment
        if ($studentAssessment) {
            $totalBalance = $studentAssessment->paymentTerms()
                ->where('balance', '>', 0)
                ->sum('balance');

            // If balance is 0 (or very close due to rounding), mark notifications as complete
            if ($totalBalance <= 0) {
                // Mark all active notifications for this user as complete
                Notification::where('user_id', $user->id)
                    ->where('is_complete', false)
                    ->update(['is_complete' => true]);

                // Also mark role-based student notifications as complete for this user
                // (but only if they were created for payment reminders, not general announcements)
                // This is optional - modify based on your business logic
            }
        }
    }
}
