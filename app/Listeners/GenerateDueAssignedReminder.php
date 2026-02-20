<?php

namespace App\Listeners;

use App\Events\DueAssigned;
use App\Models\PaymentReminder;
use Illuminate\Support\Facades\Auth;

class GenerateDueAssignedReminder
{
    /**
     * Handle the event.
     */
    public function handle(DueAssigned $event): void
    {
        $user = $event->user;
        $term = $event->term;
        
        // Calculate days until due
        $daysUntilDue = now()->diffInDays($term->due_date, false);
        
        // Determine reminder type
        if ($daysUntilDue < -1) {
            $type = PaymentReminder::TYPE_OVERDUE;
            $message = "{$term->term_name} is overdue by " . abs($daysUntilDue) . " day(s). Amount due: ₱" . number_format($term->balance, 2);
        } elseif ($daysUntilDue <= 3 && $daysUntilDue >= 0) {
            $type = PaymentReminder::TYPE_APPROACHING_DUE;
            $message = "{$term->term_name} is due in {$daysUntilDue} day(s). Amount due: ₱" . number_format($term->balance, 2);
        } else {
            $type = PaymentReminder::TYPE_PAYMENT_DUE;
            $message = "{$term->term_name} payment term assigned. Due date: " . $term->due_date->format('M d, Y') . ". Amount: ₱" . number_format($term->balance, 2);
        }
        
        // Create the reminder
        PaymentReminder::create([
            'user_id' => $user->id,
            'student_assessment_id' => $term->student_assessment_id,
            'student_payment_term_id' => $term->id,
            'type' => $type,
            'message' => $message,
            'outstanding_balance' => $term->balance,
            'status' => PaymentReminder::STATUS_SENT,
            'in_app_sent' => true,
            'sent_at' => now(),
            'trigger_reason' => PaymentReminder::TRIGGER_DUE_DATE_CHANGE,
            'triggered_by' => Auth::id(),
            'metadata' => [
                'term_order' => $term->term_order,
                'due_date' => $term->due_date,
                'percentage' => $term->percentage,
            ],
        ]);
    }
}
