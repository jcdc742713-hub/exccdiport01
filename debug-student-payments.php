<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\StudentAssessment;

// Find student
$student = Student::whereHas('user', function ($query) {
    $query->where('email', 'jcdc742713@gmail.com');
})->first();

if (!$student) {
    echo "❌ Student not found\n";
    exit;
}

echo "✓ Student Found: {$student->email} (ID: {$student->id})\n";
echo "  User ID: {$student->user_id}\n";
echo "  Student Balance: " . number_format($student->total_balance, 2) . "\n\n";

// Get current assessment
$assessment = StudentAssessment::where('user_id', $student->user_id)
    ->orderByDesc('created_at')
    ->first();

if (!$assessment) {
    echo "❌ No assessment found for user\n";
    exit;
}

echo "✓ Assessment: {$assessment->assessment_number}\n";
echo "  Total Assessment: " . number_format($assessment->total_assessment, 2) . "\n";
echo "  School Year: {$assessment->school_year}\n";
echo "  Semester: {$assessment->semester}\n\n";

// Get payment terms
$paymentTerms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
    ->orderBy('term_order')
    ->get();

echo "📊 PAYMENT TERMS STATUS:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-25s | %-15s | %-12s | %-15s | %s\n", "TERM NAME", "AMOUNT", "BALANCE", "STATUS", "PAID DATE");
echo str_repeat("-", 80) . "\n";

foreach ($paymentTerms as $term) {
    $paidDate = $term->paid_date ? $term->paid_date->format('Y-m-d H:i') : 'NULL';
    echo sprintf(
        "%-25s | %s | %s | %-15s | %s\n",
        $term->term_name,
        number_format($term->amount, 2),
        number_format($term->balance, 2),
        $term->status,
        $paidDate
    );
}

echo str_repeat("-", 80) . "\n";

// Check transactions
$transactions = Transaction::where('user_id', $student->user_id)
    ->where('kind', 'payment')
    ->orderByDesc('paid_at')
    ->get();

echo "\n💳 PAYMENT TRANSACTIONS:\n";
echo str_repeat("-", 80) . "\n";

if ($transactions->isEmpty()) {
    echo "No payment transactions found\n";
} else {
    echo sprintf("%-20s | %-15s | %-10s | %s\n", "REFERENCE", "AMOUNT", "STATUS", "PAID AT");
    echo str_repeat("-", 80) . "\n";
    foreach ($transactions as $tx) {
        echo sprintf(
            "%-20s | %s | %-10s | %s\n",
            $tx->reference,
            number_format($tx->amount, 2),
            $tx->status,
            $tx->paid_at?->format('Y-m-d H:i') ?? 'NULL'
        );
    }
}

echo str_repeat("-", 80) . "\n";

// Get raw data from database
echo "\n🔍 RAW PAYMENT TERMS DATA:\n";
$termsData = DB::table('student_payment_terms')
    ->where('student_assessment_id', $assessment->id)
    ->get(['id', 'term_name', 'term_order', 'status', 'balance', 'paid_date']);

foreach ($termsData as $term) {
    echo "Term {$term->term_order}: {$term->term_name} | Status: {$term->status} | Balance: {$term->balance} | Paid: {$term->paid_date}\n";
}

echo "\n";
