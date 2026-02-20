<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use App\Models\PaymentReminder;
use App\Models\StudentPaymentTerm;
use Illuminate\Support\Facades\Auth;

class GeneratePaymentReceivedReminder
{
    /**
     * Handle the event.
     */
    public function handle(PaymentRecorded $event): void
    {
        $user = $event->user;
        
        // Get the latest assessment
        $latestAssessment = $user->assessments()
            ->latest('created_at')
            ->first();
        
        if (!$latestAssessment) {
            return;
        }
        
        // Get payment terms to calculate remaining balance
        $paymentTerms = $latestAssessment->paymentTerms()
            ->where('balance', '>', 0)
            ->orderBy('term_order')
            ->get();
        
        $remainingBalance = $paymentTerms->sum('balance');
        
        // Build message
        if ($remainingBalance > 0) {
            $message = "Payment of ₱" . number_format($event->amount, 2) . " received. Outstanding balance: ₱" . number_format($remainingBalance, 2);
            $type = PaymentReminder::TYPE_PARTIAL_PAYMENT;
        } else {
            $message = "Payment of ₱" . number_format($event->amount, 2) . " received. Account balance fully paid!";
            $type = PaymentReminder::TYPE_PAYMENT_RECEIVED;
        }
        
        // Create payment received reminder
        PaymentReminder::create([
            'user_id' => $user->id,
            'student_assessment_id' => $latestAssessment->id,
            'student_payment_term_id' => $paymentTerms->first()?->id,
            'type' => $type,
            'message' => $message,
            'outstanding_balance' => $remainingBalance,
            'status' => PaymentReminder::STATUS_SENT,
            'in_app_sent' => true,
            'sent_at' => now(),
            'trigger_reason' => PaymentReminder::TRIGGER_ADMIN_UPDATE,
            'triggered_by' => Auth::id(),
            'metadata' => [
                'transaction_id' => $event->transactionId,
                'reference' => $event->reference,
                'payment_amount' => $event->amount,
            ],
        ]);
    }
}
