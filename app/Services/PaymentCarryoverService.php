<?php

namespace App\Services;

use App\Models\StudentPaymentTerm;
use App\Models\StudentAssessment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentCarryoverService
{
    /**
     * Apply carryover logic to all payment terms for an assessment
     */
    public function applyCarryoverToAssessment(StudentAssessment $assessment): void
    {
        $terms = $assessment->paymentTerms()
            ->orderBy('term_order')
            ->get();

        if ($terms->count() === 0) {
            return;
        }

        $carryoverBalance = 0;

        foreach ($terms as $index => $term) {
            $previousTermUnpaid = $carryoverBalance;
            $currentTermBalance = (float) $term->amount;
            
            // Total balance = unpaid from previous terms + current term
            $totalBalance = $previousTermUnpaid + $currentTermBalance;

            // Update this term's balance
            $remarks = null;
            if ($previousTermUnpaid > 0) {
                $remarks = "Balance of ₱" . number_format($previousTermUnpaid, 2) . " carried from previous term(s)";
            }

            $term->update([
                'balance' => $totalBalance,
                'remarks' => $remarks,
                'status' => $totalBalance > 0 ? 'pending' : 'paid',
            ]);

            // Carryover to next term (all unpaid balance carries)
            $carryoverBalance = $totalBalance;
        }

        // Mark the last term for carryover information
        if ($terms->count() > 0) {
            $lastTerm = $terms->last();
            if ($lastTerm->balance > 0) {
                $lastTerm->update([
                    'remarks' => ($lastTerm->remarks ? $lastTerm->remarks . '. ' : '') . 'Final term - no carryover beyond this',
                ]);
            }
        }
    }

    /**
     * Apply payment across terms using carryover priority
     * Priority: Earlier unpaid terms first, then current term
     */
    public function applyPayment(StudentAssessment $assessment, float $paymentAmount): array
    {
        $appliedPayments = [];
        $remainingAmount = $paymentAmount;

        $terms = $assessment->paymentTerms()
            ->where('balance', '>', 0)
            ->orderBy('term_order')
            ->get();

        foreach ($terms as $term) {
            if ($remainingAmount <= 0) {
                break;
            }

            $termBalance = (float) $term->balance;
            $amountToApply = min($remainingAmount, $termBalance);

            $newBalance = $termBalance - $amountToApply;
            $newStatus = $newBalance <= 0 ? 'paid' : 'partial';

            $term->update([
                'balance' => max(0, $newBalance),
                'status' => $newStatus,
                'paid_date' => $newStatus === 'paid' ? now() : $term->paid_date,
            ]);

            $appliedPayments[] = [
                'term' => $term->term_name,
                'applied' => $amountToApply,
                'remaining_balance' => max(0, $newBalance),
            ];

            $remainingAmount -= $amountToApply;
        }

        return [
            'applied_payments' => $appliedPayments,
            'remaining_amount' => $remainingAmount,
            'total_applied' => $paymentAmount - $remainingAmount,
        ];
    }

    /**
     * Get total remaining balance across all terms
     */
    public function getTotalRemainingBalance(StudentAssessment $assessment): float
    {
        return (float) $assessment->paymentTerms()
            ->sum('balance');
    }

    /**
     * Check if assessment is fully paid
     */
    public function isFullyPaid(StudentAssessment $assessment): bool
    {
        return $this->getTotalRemainingBalance($assessment) <= 0;
    }

    /**
     * Get next pending term
     */
    public function getNextPendingTerm(StudentAssessment $assessment): ?StudentPaymentTerm
    {
        return $assessment->paymentTerms()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('term_order')
            ->first();
    }

    /**
     * Get payment breakdown for display
     */
    public function getPaymentBreakdown(StudentAssessment $assessment): array
    {
        $terms = $assessment->paymentTerms()
            ->orderBy('term_order')
            ->get()
            ->map(fn($term) => [
                'id' => $term->id,
                'term_name' => $term->term_name,
                'term_order' => $term->term_order,
                'percentage' => $term->percentage,
                'original_amount' => (float) $term->amount,
                'balance' => (float) $term->balance,
                'status' => $term->status,
                'due_date' => $term->due_date->format('Y-m-d'),
                'remarks' => $term->remarks,
                'has_carryover' => $term->hasCarryover(),
            ])
            ->toArray();

        return [
            'total_assessment' => (float) $assessment->total_assessment,
            'total_paid' => (float) $assessment->total_assessment - $this->getTotalRemainingBalance($assessment),
            'total_remaining' => $this->getTotalRemainingBalance($assessment),
            'is_fully_paid' => $this->isFullyPaid($assessment),
            'terms' => $terms,
        ];
    }
}
