<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\Account;
use App\Events\PaymentRecorded;
use App\Enums\UserRoleEnum;

/**
 * StudentFirstPaymentSeeder
 *
 * Creates a realistic payment scenario where a student makes their first payment
 * for the current semester, paying only the first term (Upon Registration).
 *
 * USAGE:
 * ------
 * Run all seeders including this one:
 *   php artisan db:seed
 *
 * Run only this seeder:
 *   php artisan db:seed --class=StudentFirstPaymentSeeder
 *
 * STUDENT DETAILS:
 * ----------------
 * Email: jcdc742713@gmail.com
 * If not found, a test student will be created.
 *
 * EXPECTED OUTCOME:
 * -----------------
 * ✅ One payment transaction recorded
 * ✅ First payment term marked as paid (balance = 0, status = paid)
 * ✅ Remaining payment terms remain unpaid
 * ✅ Student account balance decreases by first term amount
 * ✅ One transaction entry in ledger
 * ✅ Payment confirmation notification triggered (queued)
 * ✅ All operations wrapped in DB transaction for atomicity
 * ✅ No duplicate records if student already has payments
 */
class StudentFirstPaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // ============================================
            // 1. FIND OR CREATE STUDENT
            // ============================================
            $student = $this->getOrCreateStudent();

            if (!$student) {
                $this->command->error('Failed to find or create student');
                return;
            }

            $this->command->info("✓ Student found/created: {$student->email} (ID: {$student->id})");

            // ============================================
            // 2. GET CURRENT SEMESTER ASSESSMENT
            // ============================================
            $assessment = $this->getCurrentSemesterAssessment($student);

            if (!$assessment) {
                $this->command->error('No assessment found for current semester');
                return;
            }

            $this->command->info("✓ Assessment found: {$assessment->assessment_number} (Total: " .
                number_format($assessment->total_assessment, 2) . ")");

            // ============================================
            // 3. GET FIRST PAYMENT TERM
            // ============================================
            $firstTerm = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
                ->where('term_order', 1)
                ->first();

            if (!$firstTerm) {
                $this->command->error('First payment term not found for assessment');
                return;
            }

            $this->command->info("✓ First term found: {$firstTerm->term_name} - Amount: " .
                number_format($firstTerm->amount, 2));

            // ============================================
            // 4. VERIFY NO EXISTING PAYMENTS
            // ============================================
            $existingPayments = Transaction::where('user_id', $student->user_id)
                ->where('kind', 'payment')
                ->where('status', 'paid')
                ->exists();

            if ($existingPayments) {
                $this->command->warn('⚠ Student already has payments. Skipping to avoid duplicates.');
                return;
            }

            // ============================================
            // 5. CREATE PAYMENT TRANSACTION
            // ============================================
            $paymentAmount = (float) $firstTerm->amount;
            $paymentReference = 'FIRST-PAY-' . strtoupper(uniqid());
            $paymentDate = Carbon::now();

            $transaction = Transaction::create([
                'user_id' => $student->user_id,
                'account_id' => $student->user->account?->id,
                'reference' => $paymentReference,
                'payment_channel' => 'cash',
                'kind' => 'payment',
                'type' => 'regular_payment',
                'amount' => $paymentAmount,
                'status' => 'paid',
                'paid_at' => $paymentDate,
                'meta' => [
                    'term_id' => $firstTerm->id,
                    'term_name' => $firstTerm->term_name,
                    'assessment_id' => $assessment->id,
                    'payment_method' => 'seeder',
                    'description' => 'First payment - Upon Registration term',
                    'student_id' => $student->id,
                ],
            ]);

            $this->command->info("✓ Transaction created: {$paymentReference} for " .
                number_format($paymentAmount, 2));

            // ============================================
            // 6. UPDATE FIRST PAYMENT TERM
            // ============================================
            $firstTerm->update([
                'status' => StudentPaymentTerm::STATUS_PAID,
                'balance' => 0,
                'paid_date' => $paymentDate,
            ]);

            $this->command->info("✓ First term marked as PAID (balance set to 0)");

            // ============================================
            // 7. UPDATE STUDENT ACCOUNT BALANCE
            // ============================================
            $student->update([
                'total_balance' => max(0, $student->total_balance - $paymentAmount),
            ]);

            $this->command->info("✓ Student balance updated: " .
                number_format($student->total_balance - $paymentAmount, 2));

            // ============================================
            // 8. VERIFY REMAINING TERMS STAY UNPAID
            // ============================================
            $remainingTerms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
                ->where('term_order', '>', 1)
                ->get();

            foreach ($remainingTerms as $term) {
                // Verify status hasn't changed
                if ($term->status !== StudentPaymentTerm::STATUS_PENDING) {
                    $this->command->warn("⚠ Term {$term->term_name} status changed to {$term->status}");
                }
            }

            $unpaidCount = $remainingTerms->count();
            $this->command->info("✓ Verified {$unpaidCount} remaining terms stay unpaid");

            // ============================================
            // 9. TRIGGER PAYMENT RECORDED EVENT
            // ============================================
            // This will dispatch queued listeners for notifications
            PaymentRecorded::dispatch(
                user: $student->user,
                transactionId: $transaction->id,
                amount: $paymentAmount,
                reference: $paymentReference,
            );
            $this->command->info("✓ PaymentRecorded event dispatched (notifications queued)");

            // ============================================
            // 10. SUMMARY
            // ============================================
            $this->command->info("\n" . str_repeat("=", 60));
            $this->command->info("✅ FIRST PAYMENT SEEDER COMPLETED SUCCESSFULLY");
            $this->command->info(str_repeat("=", 60));
            $this->command->info("Summary:");
            $this->command->info("  • Student: {$student->email}");
            $this->command->info("  • Assessment: {$assessment->assessment_number}");
            $this->command->info("  • Payment Amount: " . number_format($paymentAmount, 2));
            $this->command->info("  • Term Paid: {$firstTerm->term_name}");
            $this->command->info("  • Reference: {$paymentReference}");
            $this->command->info("  • Timestamp: {$paymentDate->format('Y-m-d H:i:s')}");
            $this->command->info("  • Remaining Terms: {$unpaidCount} (unpaid)");
            $this->command->info("  • Student New Balance: " .
                number_format($student->total_balance - $paymentAmount, 2));
            $this->command->info(str_repeat("=", 60) . "\n");
        });
    }

    /**
     * Find student by email or create a test student
     */
    private function getOrCreateStudent(): ?Student
    {
        $email = 'jcdc742713@gmail.com';

        // Try to find existing student
        $student = Student::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if ($student) {
            return $student;
        }

        // Try to find user first
        $user = User::where('email', $email)->first();

        if ($user) {
            // User exists but no student record - create one
            $student = Student::create([
                'user_id' => $user->id,
                'email' => $email,
                'first_name' => 'Test',
                'last_name' => 'Student',
                'student_id' => 'TS' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
                'course' => 'Computer Science',
                'year_level' => '2nd Year',
                'total_balance' => 15000,
                'enrollment_status' => 'active',
            ]);

            return $student;
        }

        // Create new user and student
        $user = User::create([
            'name' => 'Test Student',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => UserRoleEnum::STUDENT->value,
        ]);

        // Ensure user has an account
        if (!$user->account) {
            Account::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);
        }

        $student = Student::create([
            'user_id' => $user->id,
            'email' => $email,
            'first_name' => 'Test',
            'last_name' => 'Student',
            'student_id' => 'TS' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'course' => 'Computer Science',
            'year_level' => '2nd Year',
            'total_balance' => 15000,
            'enrollment_status' => 'active',
        ]);

        return $student;
    }

    /**
     * Get current semester assessment for student
     */
    private function getCurrentSemesterAssessment(Student $student): ?StudentAssessment
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        // Determine semester (1st sem: June-November, 2nd sem: December-May)
        $semester = $currentMonth >= 6 ? '1st Sem' : '2nd Sem';
        $schoolYear = $currentMonth >= 6
            ? "{$currentYear}-" . ($currentYear + 1)
            : ($currentYear - 1) . "-{$currentYear}";

        // Find or create assessment
        $assessment = StudentAssessment::where('user_id', $student->user_id)
            ->where('semester', $semester)
            ->where('school_year', $schoolYear)
            ->orderByDesc('created_at')
            ->first();

        if (!$assessment) {
            // Look for any recent assessment
            $assessment = StudentAssessment::where('user_id', $student->user_id)
                ->orderByDesc('created_at')
                ->first();
        }

        return $assessment;
    }
}
