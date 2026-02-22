<?php

namespace App\Services;

use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

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
     * @param array $data Payment details (payment_method, paid_at, description, selected_term_id, term_name)
     * @param bool $requiresApproval Whether this payment needs accounting approval
     * @return array Payment result with carryover info and workflow details
     * @throws Exception
     */
    public function processPayment(User $user, float $paymentAmount, array $data, bool $requiresApproval = false): array
    {
        return DB::transaction(function () use ($user, $paymentAmount, $data, $requiresApproval) {
            // Get latest assessment
            $assessment = StudentAssessment::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            if (!$assessment) {
                throw new Exception('No active assessment found for student.');
            }

            // Determine transaction status based on approval requirement
            $transactionStatus = $requiresApproval ? 'awaiting_approval' : 'paid';

            // Record transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'reference' => 'PAY-' . strtoupper(Str::random(8)),
                'kind' => 'payment',
                'type' => 'Payment: ' . ($data['term_name'] ?? 'General'),
                'amount' => $paymentAmount,
                'status' => $transactionStatus,
                'payment_channel' => $data['payment_method'] ?? 'cash',
                'paid_at' => $transactionStatus === 'paid' ? ($data['paid_at'] ?? now()) : null,
                'meta' => [
                    'description' => $data['description'] ?? 'Payment',
                    'term_name' => $data['term_name'] ?? null,
                    'selected_term_id' => $data['selected_term_id'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                ],
            ]);

            // Only update payment terms and balance if payment is immediately approved
            $paymentBreakdown = [];
            if (!$requiresApproval) {
                $paymentBreakdown = $this->applyPaymentWithCarryover($assessment, $paymentAmount, $data['selected_term_id'] ?? null);
                $this->updateStudentBalance($user);
            }
            // If requiresApproval=true, terms are NOT updated yet.
            // They will be updated in finalizeApprovedPayment() after accounting approves.

            // Start workflow if approval is required
            $workflowInstance = null;
            if ($requiresApproval) {
                $workflowInstance = $this->startPaymentApprovalWorkflow($transaction, $user->id, $data);
            }

            return [
                'success'              => true,
                'transaction_id'       => $transaction->id,
                'transaction_reference' => $transaction->reference,
                'payment_breakdown'    => $paymentBreakdown,
                'requires_approval'    => $requiresApproval,
                'workflow_instance_id' => $workflowInstance?->id,
                'message'              => $requiresApproval
                    ? 'Payment submitted successfully. Awaiting accounting verification.'
                    : ($paymentBreakdown ? $this->generatePaymentMessage($paymentBreakdown) : 'Payment recorded successfully.'),
            ];
        });
    }

    /**
     * Apply payment across terms with carryover logic
     * 
     * Payment prioritizes:
     * 1. Selected term (if specified) - applies full payment to this term first
     * 2. Earlier unpaid terms with carryover (if overpayment)
     * 3. Remaining balance in subsequent terms (if still overpayment)
     * 
     * @param StudentAssessment $assessment
     * @param float $paymentAmount
     * @param int|null $selectedTermId - specific term to apply payment to
     * @return array
     */
    private function applyPaymentWithCarryover(StudentAssessment $assessment, float $paymentAmount, ?int $selectedTermId = null): array
    {
        $breakdown = [];
        $remainingPayment = $paymentAmount;

        // If specific term is selected, apply payment to that term first
        if ($selectedTermId) {
            $selectedTerm = $assessment->paymentTerms()
                ->where('id', $selectedTermId)
                ->first();

            if ($selectedTerm && $selectedTerm->balance > 0) {
                $previousBalance = (float) $selectedTerm->balance;
                $amountApplied = min($remainingPayment, $previousBalance);
                $newBalance = $previousBalance - $amountApplied;
                $newStatus = $newBalance <= 0 ? self::STATUS_PAID : self::STATUS_PARTIAL;

                // Update selected term
                $selectedTerm->update([
                    'balance' => max(0, $newBalance),
                    'status' => $newStatus,
                    'paid_date' => $newStatus === self::STATUS_PAID ? now() : $selectedTerm->paid_date,
                ]);

                $breakdown[] = [
                    'term_id' => $selectedTerm->id,
                    'term_name' => $selectedTerm->term_name,
                    'term_order' => $selectedTerm->term_order,
                    'previous_balance' => $previousBalance,
                    'amount_applied' => $amountApplied,
                    'new_balance' => max(0, $newBalance),
                    'status' => $newStatus,
                    'has_carryover' => $newBalance > 0,
                ];

                $remainingPayment -= $amountApplied;
            }
        }

        // Apply remaining payment to other unpaid terms in order (carryover)
        if ($remainingPayment > 0) {
            $terms = $assessment->paymentTerms()
                ->where('balance', '>', 0)
                ->when($selectedTermId, function ($query) use ($selectedTermId) {
                    // Skip the selected term if already processed
                    return $query->where('id', '!=', $selectedTermId);
                })
                ->orderBy('term_order')
                ->get();

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
        }

        // Handle overpayment (if any payment remains after all terms paid)
        if ($remainingPayment > 0) {
            $breakdown[] = [
                'overpayment' => $remainingPayment,
                'note' => 'Overpayment applied to future assessments',
            ];
        }

        // If no breakdown (shouldn't happen), return error info
        if (empty($breakdown)) {
            return [
                [
                    'note' => 'No outstanding balance to apply payment to',
                    'overpayment' => $paymentAmount,
                ]
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

    /**
     * Start the payment approval workflow for a student-submitted transaction
     */
    private function startPaymentApprovalWorkflow(Transaction $transaction, int $userId, array $data): WorkflowInstance
    {
        $workflow = Workflow::where('type', 'payment_approval')
            ->where('is_active', true)
            ->firstOrFail();

        $workflowService = app(WorkflowService::class);
        
        $instance = $workflowService->startWorkflow($workflow, $transaction, $userId);
        
        // Store payment data in instance metadata for accounting to see during review
        $instance->update([
            'metadata' => [
                'transaction_id'   => $transaction->id,
                'amount'           => $transaction->amount,
                'payment_method'   => $data['payment_method'] ?? null,
                'selected_term_id' => $data['selected_term_id'] ?? null,
                'term_name'        => $data['term_name'] ?? null,
                'student_user_id'  => $userId,
                'submitted_at'     => now()->toIso8601String(),
            ],
        ]);

        // Immediately advance to "Accounting Verification" step (step 2, which requires_approval)
        $workflowService->advanceWorkflow($instance, $userId);

        return $instance->fresh();
    }

    /**
     * Finalize a payment after accounting approval.
     * Called by WorkflowService after the last approval is granted.
     * Updates payment terms, balance, marks transaction as paid.
     */
    public function finalizeApprovedPayment(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $user = $transaction->user;

            $assessment = StudentAssessment::where('user_id', $user->id)
                ->latest('created_at')
                ->first();

            if (!$assessment) {
                throw new Exception('Assessment not found during payment finalization.');
            }

            // Get term ID from transaction meta
            $selectedTermId = $transaction->meta['selected_term_id'] ?? null;

            // Apply payment to terms
            $this->applyPaymentWithCarryover($assessment, (float) $transaction->amount, $selectedTermId);

            // Mark transaction as paid
            $transaction->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            // Update student account balance
            $this->updateStudentBalance($user);
        });
    }

    /**
     * Cancel a pending payment after accounting rejection.
     * Marks transaction as cancelled. Terms are NOT updated (payment never applied).
     */
    public function cancelRejectedPayment(Transaction $transaction): void
    {
        $transaction->update(['status' => 'cancelled']);
    }
}
