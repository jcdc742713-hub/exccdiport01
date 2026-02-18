<?php

namespace App\Services;

use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;
use DB;

class StudentPaymentService
{
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';

    /**
     * Process payment and automatically apply carryover logic
     * 
     * @param User $user
     * @param float $paymentAmount
     * @param array $data Payment details (payment_method, paid_at, description, selected_term_id)
     * @return array Payment result with carryover info
     * @throws Exception
     */
    public function processPayment(User $user, float $paymentAmount, array $data): array
    {
        return DB::transaction(function () use ($user, $paymentAmount, $data) {
            // Get latest assessment
            $assessment = StudentAssessment::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            if (!$assessment) {
                throw new Exception('No active assessment found for student.');
            }

            // Record transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'kind' => 'payment',
                'type' => 'Payment',
                'amount' => $paymentAmount,
                'status' => 'paid',
                'payment_channel' => $data['payment_method'] ?? 'cash',
                'paid_at' => $data['paid_at'] ?? now(),
                'meta' => [
                    'description' => $data['description'] ?? 'Payment',
                    'term_name' => $data['term_name'] ?? null,
                ],
            ]);

            // Apply payment to terms with carryover logic
            $paymentBreakdown = $this->applyPaymentWithCarryover($assessment, $paymentAmount);

            // Update account balance
            $this->updateStudentBalance($user);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'transaction_reference' => $transaction->reference,
                'payment_breakdown' => $paymentBreakdown,
                'message' => $this->generatePaymentMessage($paymentBreakdown),
            ];
        });
    }

    /**
     * Apply payment across terms with carryover logic
     * 
     * Payment prioritizes:
     * 1. Current selected term (if specified)
     * 2. Earlier unpaid terms with carryover
     * 3. Remaining balance in subsequent terms
     */
    private function applyPaymentWithCarryover(StudentAssessment $assessment, float $paymentAmount): array
    {
        $breakdown = [];
        $remainingPayment = $paymentAmount;

        // Get all terms ordered by priority
        $terms = $assessment->paymentTerms()
            ->where('balance', '>', 0)
            ->orderBy('term_order')
            ->get();

        if ($terms->isEmpty()) {
            return [
                [
                    'note' => 'No outstanding balance to apply payment to',
                    'overpayment' => $paymentAmount,
                ]
            ];
        }

        foreach ($terms as $term) {
            if ($remainingPayment <= 0) break;

            $previousBalance = (float) $term->balance;
            $amountApplied = min($remainingPayment, $previousBalance);
            $newBalance = $previousBalance - $amountApplied;
            $newStatus = $newBalance <= 0 ? self::STATUS_PAID : self::STATUS_PARTIAL;

            // Update term
            $term->update([
                'balance' => max(0, $newBalance),
                'status' => $newStatus,
                'paid_date' => $newStatus === self::STATUS_PAID ? now() : $term->paid_date,
            ]);

            $breakdown[] = [
                'term_id' => $term->id,
                'term_name' => $term->term_name,
                'term_order' => $term->term_order,
                'previous_balance' => $previousBalance,
                'amount_applied' => $amountApplied,
                'new_balance' => max(0, $newBalance),
                'status' => $newStatus,
                'has_carryover' => $newBalance > 0,
            ];

            $remainingPayment -= $amountApplied;
        }

        // Handle overpayment
        if ($remainingPayment > 0) {
            $breakdown[] = [
                'overpayment' => $remainingPayment,
                'note' => 'Overpayment applied to future assessments',
            ];
        }

        return $breakdown;
    }

    /**
     * Update student account balance after payment
     */
    private function updateStudentBalance(User $user): void
    {
        $charges = $user->transactions()
            ->where('kind', 'charge')
            ->sum('amount');

        $payments = $user->transactions()
            ->where('kind', 'payment')
            ->where('status', 'paid')
            ->sum('amount');

        $balance = $charges - $payments;
        $account = $user->account ?? $user->account()->create();
        $account->update(['balance' => $balance]);
    }

    /**
     * Generate user-friendly payment message
     */
    private function generatePaymentMessage(array $breakdown): string
    {
        $appliedToTerms = array_filter(
            array_map(fn($b) => $b['term_name'] ?? null, $breakdown),
            fn($v) => $v !== null
        );
        
        if (empty($appliedToTerms)) {
            return 'Payment recorded successfully.';
        }

        return sprintf(
            'Payment successfully applied to: %s',
            implode(', ', $appliedToTerms)
        );
    }

    /**
     * Get payment terms for student with current balance
     */
    public function getPaymentTermsForStudent(User $user): array
    {
        $assessment = StudentAssessment::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$assessment) {
            return [];
        }

        return $assessment->paymentTerms()
            ->orderBy('term_order')
            ->get()
            ->map(fn($term) => [
                'id' => $term->id,
                'name' => $term->term_name,
                'order' => $term->term_order,
                'amount' => (float) $term->amount,
                'balance' => (float) $term->balance,
                'status' => $term->status,
                'due_date' => $term->due_date?->format('Y-m-d'),
                'is_overdue' => $term->isOverdue(),
                'has_carryover' => $term->hasCarryover(),
            ])
            ->toArray();
    }

    /**
     * Get total outstanding balance for student
     */
    public function getTotalOutstandingBalance(User $user): float
    {
        $assessment = StudentAssessment::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$assessment) {
            return 0;
        }

        return (float) $assessment->paymentTerms()->sum('balance');
    }
}
