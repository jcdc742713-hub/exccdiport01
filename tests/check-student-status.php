<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\StudentAssessment;
use Illuminate\Support\Facades\DB;

// Find student
$user = User::where('email', 'jcdc742713@gmail.com')->first();

if (!$user) {
    echo "❌ User with email jcdc742713@gmail.com not found in database\n";
    exit(1);
}

echo "✓ User Found: {$user->email} (User ID: {$user->id})\n";
echo "  Name: {$user->name}\n";
echo "  Role: {$user->role->value}\n\n";

$student = Student::where('user_id', $user->id)->first();

if (!$student) {
    // Show raw database data
    echo "❌ Student model record not found for user_id {$user->id}\n";
    echo "\nChecking if there are payment terms with this user_id...\n";
    
    // Check payment terms with user_id
    $paymentTermsRaw = DB::table('student_payment_terms')->where('user_id', $user->id)->get();
    if ($paymentTermsRaw->isNotEmpty()) {
        echo "✓ Found " . count($paymentTermsRaw) . " payment terms with user_id {$user->id}:\n";
        foreach ($paymentTermsRaw as $term) {
            echo "  - {$term->term_name} | Status: {$term->status} | Balance: {$term->balance} | Paid: {$term->paid_date}\n";
        }
    }
    
    // Check transactions
    $transactionsRaw = DB::table('transactions')->where('user_id', $user->id)->where('kind', 'payment')->get();
    if ($transactionsRaw->isNotEmpty()) {
        echo "\n✓ Found " . count($transactionsRaw) . " payment transactions with user_id {$user->id}:\n";
        foreach ($transactionsRaw as $tx) {
            echo "  - {$tx->reference} | Amount: {$tx->amount} | Status: {$tx->status} | Paid: {$tx->paid_at}\n";
        }
    }
    
    // Check assessments
    $assessmentsRaw = DB::table('student_assessments')->where('user_id', $user->id)->limit(5)->get();
    if ($assessmentsRaw->isNotEmpty()) {
        echo "\n✓ Found " . count($assessmentsRaw) . " assessments with user_id {$user->id}:\n";
        foreach ($assessmentsRaw as $a) {
            echo "  - {$a->assessment_number} | Total: {$a->total_assessment} | Year: {$a->school_year} | Sem: {$a->semester}\n";
        }
    }
    
    exit(1);
}

echo "✓ Student Record Found (Student ID: {$student->id})\n";
echo "  Total Balance: " . number_format($student->total_balance, 2) . "\n";
echo "  Email: {$student->email}\n\n";

// Get current assessment
$assessment = StudentAssessment::where('user_id', $user->id)
    ->orderByDesc('created_at')
    ->first();

if (!$assessment) {
    echo "❌ No assessment found for this user\n";
    exit(1);
}

echo "✓ Current Assessment: {$assessment->assessment_number}\n";
echo "  Total Amount: " . number_format($assessment->total_assessment, 2) . "\n";
echo "  School Year: {$assessment->school_year}\n";
echo "  Semester: {$assessment->semester}\n\n";

// Get payment terms
$paymentTerms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
    ->orderBy('term_order')
    ->get();

echo "📊 PAYMENT TERMS STATUS:\n";
echo str_repeat("-", 100) . "\n";
printf("%-5s | %-25s | %-12s | %-12s | %-15s | %-20s\n", "ORDER", "TERM NAME", "AMOUNT", "BALANCE", "STATUS", "PAID DATE");
echo str_repeat("-", 100) . "\n";

foreach ($paymentTerms as $term) {
    $paidDate = $term->paid_date ? $term->paid_date->format('Y-m-d H:i:s') : 'NOT PAID';
    printf(
        "%-5d | %-25s | %s | %s | %-15s | %-20s\n",
        $term->term_order,
        $term->term_name,
        number_format($term->amount, 2),
        number_format($term->balance, 2),
        $term->status,
        $paidDate
    );
}

echo str_repeat("-", 100) . "\n";

// Check transactions
$transactions = Transaction::where('user_id', $user->id)
    ->where('kind', 'payment')
    ->orderByDesc('paid_at')
    ->get();

echo "\n💳 PAYMENT TRANSACTIONS:\n";
echo str_repeat("-", 100) . "\n";

if ($transactions->isEmpty()) {
    echo "No payment transactions found\n";
} else {
    printf("%-25s | %-12s | %-10s | %-20s | %s\n", "REFERENCE", "AMOUNT", "STATUS", "PAID AT", "METHOD");
    echo str_repeat("-", 100) . "\n";
    foreach ($transactions as $tx) {
        printf(
            "%-25s | %s | %-10s | %-20s | %s\n",
            $tx->reference,
            number_format($tx->amount, 2),
            $tx->status,
            $tx->paid_at?->format('Y-m-d H:i:s') ?? 'NULL',
            $tx->payment_channel ?? 'UNKNOWN'
        );
    }
}

echo str_repeat("-", 100) . "\n";

// Summary
$paidTermsCount = $paymentTerms->where('status', 'paid')->count();
$pendingTermsCount = $paymentTerms->where('status', 'pending')->count();

echo "\n📈 SUMMARY:\n";
echo "  Total Payment Terms: " . $paymentTerms->count() . "\n";
echo "  ✅ Paid Terms: {$paidTermsCount}\n";
echo "  ⏳ Pending Terms: {$pendingTermsCount}\n";
echo "  Total Paid Amount: " . number_format($transactions->sum('amount'), 2) . "\n";
echo "  Outstanding Balance: " . number_format($paymentTerms->sum('balance'), 2) . "\n\n";

exit(0);
