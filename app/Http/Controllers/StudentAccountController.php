<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Fee;
use App\Models\StudentAssessment;
use App\Models\Notification;

class StudentAccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Load account
        if (!$user->account) {
            $user->account()->create(['balance' => 0]);
        }
        
        // Load transactions directly through user relationship (NOT account)
        // This is the key fix - seeders use user_id, not account_id
        $user->load(['transactions' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        // Get current term
        $year = now()->year;
        $month = now()->month;
        if ($month >= 6 && $month <= 10) {
            $semester = '1st Sem';
        } elseif ($month >= 11 || $month <= 3) {
            $semester = '2nd Sem';
        } else {
            $semester = 'Summer';
        }

        // Get fees for current term and student's year level
        $fees = Fee::active()
            ->where('year_level', $user->year_level)
            ->where('semester', $semester)
            ->where('school_year', $year . '-' . ($year + 1))
            ->select('name', 'amount', 'category')
            ->get();

        // If no fees found, use fallback
        if ($fees->isEmpty()) {
            $fees = collect([
                ['name' => 'Registration Fee', 'amount' => 200.0, 'category' => 'Miscellaneous'],
                ['name' => 'Tuition Fee', 'amount' => 5000.0, 'category' => 'Tuition'],
                ['name' => 'Lab Fee', 'amount' => 2000.0, 'category' => 'Laboratory'],
                ['name' => 'Library Fee', 'amount' => 500.0, 'category' => 'Library'],
                ['name' => 'Misc. Fee', 'amount' => 1200.0, 'category' => 'Miscellaneous'],
            ]);
        }

        // Get latest assessment with payment terms
        $latestAssessment = StudentAssessment::where('user_id', $user->id)
            ->with('paymentTerms')
            ->latest('created_at')
            ->first();

        $paymentTerms = [];
        if ($latestAssessment) {
            $paymentTerms = $latestAssessment->paymentTerms()
                ->orderBy('term_order')
                ->get()
                ->map(function ($term) {
                    return [
                        'id' => $term->id,
                        'term_name' => $term->term_name,
                        'term_order' => $term->term_order,
                        'percentage' => $term->percentage,
                        'amount' => (float) $term->amount,
                        'balance' => (float) $term->balance,
                        'due_date' => $term->due_date,
                        'status' => $term->status,
                        'remarks' => $term->remarks,
                        'paid_date' => $term->paid_date,
                    ];
                })
                ->toArray();
        }

        // Get active notifications for this user, including term-based filtering
        $notificationsCollection = Notification::where('is_active', true)
            ->where(function ($query) use ($user) {
                // If notification has a specific user_id, only show to that user
                $query->where(function ($q) use ($user) {
                    // Show notifications targeted to a specific user (personal notifications like payment alerts)
                    $q->where('user_id', $user->id);
                })
                ->orWhere(function ($q) use ($user) {
                    // Show role-based notifications with NO specific user_id (broadcast to all users of that role)
                    $q->whereNull('user_id')
                      ->where(function ($q2) use ($user) {
                          $q2->where('target_role', $user->role)
                             ->orWhere('target_role', 'all');
                      });
                });
            })
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter notifications by term-based criteria
        $notifications = $notificationsCollection->filter(function ($notification) use ($paymentTerms, $user) {
            // DEBUG: Log all notifications being checked
            \Log::info('Checking notification: ' . $notification->title, [
                'term_ids' => $notification->term_ids,
                'target_term_name' => $notification->target_term_name,
                'trigger_days_before_due' => $notification->trigger_days_before_due,
                'has_payment_terms' => !empty($paymentTerms),
            ]);

            // If notification has no term-based filtering, show to all
            $hasTermIds = !empty($notification->term_ids) && is_array($notification->term_ids) && count($notification->term_ids) > 0;
            $hasTermName = !empty($notification->target_term_name);
            
            if (!$hasTermIds && !$hasTermName) {
                \Log::info('  -> No term filters, showing to all');
                return true;
            }

            // If student has no payment terms, don't show term-specific notifications
            if (empty($paymentTerms)) {
                \Log::info('  -> Term filtering required but student has no payment terms');
                return false;
            }

            $today = now();

            // Filter by specific term IDs
            if ($hasTermIds) {
                $termIds = $notification->term_ids;
                
                // Check if any of the student's payment terms match the notification's term IDs
                $hasMatchingTerm = false;
                $withinDueDateRange = true;

                foreach ($paymentTerms as $term) {
                    if (in_array($term['id'], $termIds)) {
                        $hasMatchingTerm = true;
                        
                        // If trigger_days_before_due is set, check if we're within that range
                        if (!is_null($notification->trigger_days_before_due) && !empty($term['due_date'])) {
                            $dueDate = \Carbon\Carbon::parse($term['due_date']);
                            $daysBeforeDue = $dueDate->diffInDays($today, false);
                            
                            // Show if we're between (trigger_days_before_due) days before and the due date
                            if ($daysBeforeDue < 0 || $daysBeforeDue > $notification->trigger_days_before_due) {
                                $withinDueDateRange = false;
                            }
                        }
                        break;
                    }
                }

                return $hasMatchingTerm && $withinDueDateRange;
            }

            // Filter by term name
            if ($hasTermName) {
                $targetTermName = $notification->target_term_name;
                
                $hasMatchingTerm = false;
                $withinDueDateRange = true;

                foreach ($paymentTerms as $term) {
                    if ($term['term_name'] === $targetTermName) {
                        $hasMatchingTerm = true;
                        
                        // If trigger_days_before_due is set, check if we're within that range
                        if (!is_null($notification->trigger_days_before_due) && !empty($term['due_date'])) {
                            $dueDate = \Carbon\Carbon::parse($term['due_date']);
                            $daysBeforeDue = $dueDate->diffInDays($today, false);
                            
                            // Show if we're between (trigger_days_before_due) days before and the due date
                            if ($daysBeforeDue < 0 || $daysBeforeDue > $notification->trigger_days_before_due) {
                                $withinDueDateRange = false;
                            }
                        }
                        break;
                    }
                }

                return $hasMatchingTerm && $withinDueDateRange;
            }

            return true;
        });

        return Inertia::render('Student/AccountOverview', [
            'account'                   => $user->account,
            'transactions'              => $user->transactions ?? [], // Use user->transactions, not account->transactions
            'fees'                      => $fees->values(),
            'latestAssessment'          => $latestAssessment,
            'paymentTerms'              => $paymentTerms,
            'notifications'             => $notifications,
            'pendingApprovalPayments'   => $user->transactions
                ->filter(function ($t) {
                    return $t->kind === 'payment' && $t->status === 'awaiting_approval';
                })
                ->map(function ($t) {
                    return [
                        'id'                => $t->id,
                        'reference'         => $t->reference,
                        'amount'            => (float) $t->amount,
                        'selected_term_id'  => $t->meta['selected_term_id'] ?? null,
                        'term_name'         => $t->meta['term_name'] ?? 'General',
                        'created_at'        => $t->created_at,
                    ];
                })
                ->values(),
        ]);
    }
}