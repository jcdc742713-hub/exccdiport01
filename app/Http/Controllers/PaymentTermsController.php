<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentPaymentTerm;
use App\Models\Notification;

class PaymentTermsController extends Controller
{
    /**
     * Display Payment Terms Management page
     */
    public function index()
    {
        $this->authorize('managePaymentTerms', StudentPaymentTerm::class);

        // Get all payment terms with student information
        $paymentTerms = StudentPaymentTerm::with(['studentAssessment.user.student'])
            ->orderBy('due_date')
            ->get()
            ->map(function ($term) {
                $student = $term->studentAssessment->user->student;
                return [
                    'id' => $term->id,
                    'term_name' => $term->term_name,
                    'term_order' => $term->term_order,
                    'amount' => (float) $term->amount,
                    'balance' => (float) $term->balance,
                    'due_date' => $term->due_date,
                    'status' => $term->status,
                    'student_id' => $student->student_id ?? 'Unknown',
                    'student_name' => $term->studentAssessment->user->name,
                    'assessment_id' => $term->student_assessment_id,
                ];
            });

        $unsetDueDatesCount = StudentPaymentTerm::whereNull('due_date')->count();

        return Inertia::render('Admin/PaymentTermsManagement', [
            'payment_terms' => $paymentTerms,
            'unsetDueDatesCount' => $unsetDueDatesCount,
        ]);
    }

    /**
     * Update due date for a payment term
     */
    public function updateDueDate(Request $request, StudentPaymentTerm $paymentTerm)
    {
        $this->authorize('update', $paymentTerm);

        $validated = $request->validate([
            'due_date' => 'required|date',
        ]);

        // Update the payment term's due date
        $paymentTerm->update([
            'due_date' => $validated['due_date'],
        ]);

        // Create a notification for students about the due date update
        $this->createDueDateNotification($paymentTerm);

        return back()->with('success', 'Due date updated successfully. Student has been notified.');
    }

    /**
     * Create notification to student about due date update
     */
    private function createDueDateNotification(StudentPaymentTerm $paymentTerm): void
    {
        $user = $paymentTerm->studentAssessment->user;
        $dueDateFormatted = $paymentTerm->due_date->format('F j, Y');

        Notification::create([
            'title' => "Payment Due Date Set: {$paymentTerm->term_name}",
            'message' => "Your payment for {$paymentTerm->term_name} is now due on {$dueDateFormatted}. Amount: ₱" . number_format($paymentTerm->amount, 2),
            'type' => 'payment_due',
            'target_role' => 'student',
            'user_id' => $user->id,
            'start_date' => now()->toDateString(),
            'end_date' => $paymentTerm->due_date->toDateString(),
            'is_active' => true,
        ]);
    }
}
