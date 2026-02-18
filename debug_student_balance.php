<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

echo "=== DEBUG: Student Balance Analysis ===\n\n";

$user = User::where('email', 'jcdc742713@gmail.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

echo "👤 USER DATA:\n";
echo "  Email: {$user->email}\n";
echo "  ID: {$user->id}\n";
echo "  Role: {$user->role->value}\n\n";

// Check account
$account = $user->account;
echo "💰 ACCOUNT DATA:\n";
if ($account) {
    echo "  Account ID: {$account->id}\n";
    echo "  Account Balance: " . number_format($account->balance, 2) . "\n";
} else {
    echo "  ❌ No account found\n";
}
echo "\n";

// Check student record
$student = $user->student;
echo "🎓 STUDENT RECORD:\n";
if ($student) {
    echo "  Student ID: {$student->id}\n";
    echo "  Student.total_balance: " . number_format($student->total_balance, 2) . "\n";
} else {
    echo "  ❌ No student record found\n";
}
echo "\n";

// Check assessments
$assessments = StudentAssessment::where('user_id', $user->id)->get();
echo "📋 ASSESSMENTS (" . $assessments->count() . " total):\n";
foreach ($assessments as $assessment) {
    echo "  • {$assessment->assessment_number} ({$assessment->semester} {$assessment->school_year})\n";
    echo "    Total: " . number_format($assessment->total_assessment, 2) . "\n";
    
    $terms = $assessment->paymentTerms()->get();
    foreach ($terms as $term) {
        $status_icon = $term->status === 'paid' ? '✓' : '○';
        echo "    {$status_icon} {$term->term_name}: " . number_format($term->amount, 2) . 
             " | Balance: " . number_format($term->balance, 2) . 
             " | Status: {$term->status}\n";
    }
}
echo "\n";

// Check transactions
$charges = $user->transactions()->where('kind', 'charge')->get();
$payments = $user->transactions()->where('kind', 'payment')->get();

echo "📊 TRANSACTIONS:\n";
echo "  Charges (" . $charges->count() . "):\n";
$totalCharges = 0;
foreach ($charges as $tx) {
    echo "    • " . number_format($tx->amount, 2) . " ({$tx->status}) - {$tx->reference}\n";
    $totalCharges += $tx->amount;
}
echo "    Total Charges: " . number_format($totalCharges, 2) . "\n\n";

echo "  Payments (" . $payments->count() . "):\n";
$totalPaid = 0;
foreach ($payments as $tx) {
    echo "    • " . number_format($tx->amount, 2) . " ({$tx->status}) - {$tx->reference}\n";
    if ($tx->status === 'paid') $totalPaid += $tx->amount;
}
echo "    Total Paid: " . number_format($totalPaid, 2) . "\n\n";

$calculatedBalance = $totalCharges - $totalPaid;
echo "🔢 BALANCE CALCULATION:\n";
echo "  Total Charges: " . number_format($totalCharges, 2) . "\n";
echo "  Total Paid: " . number_format($totalPaid, 2) . "\n";
echo "  Calculated Balance: " . number_format($calculatedBalance, 2) . "\n";
if ($account) {
    echo "  Account.balance: " . number_format($account->balance, 2) . "\n";
    echo "  Student.total_balance: " . number_format($student?->total_balance ?? 0, 2) . "\n";
}
echo "\n";

// Check outstanding balance from payment terms
$outstandingTermBalance = 0;
foreach ($assessments as $assessment) {
    $outstanding = $assessment->paymentTerms()
        ->where('status', '!=', 'paid')
        ->sum('balance');
    $outstandingTermBalance += $outstanding;
}

echo "📈 OUTSTANDING PAYMENT TERMS:\n";
echo "  Total Outstanding: " . number_format($outstandingTermBalance, 2) . "\n";
echo "\n";

echo "⚠️  DISCREPANCIES:\n";
if ($account && abs($account->balance - $calculatedBalance) > 0.01) {
    echo "  ❌ Account balance != calculated: " . number_format($account->balance - $calculatedBalance, 2) . " diff\n";
} else {
    echo "  ✓ Account balance is consistent\n";
}

if ($totalCharges > 0 && $totalPaid === 0) {
    echo "  ⚠️  Student has " . number_format($totalCharges, 2) . " in charges but 0 paid\n";
} elseif ($totalCharges > 0 && $totalPaid >= $totalCharges) {
    echo "  ⚠️  Student appears FULLY PAID or OVERPAID\n";
    echo "       Charges: " . number_format($totalCharges, 2) . "\n";
    echo "       Paid: " . number_format($totalPaid, 2) . "\n";
}

if ($outstandingTermBalance > 0.01 && $totalPaid === 0) {
    echo "  ⚠️  Payment terms show " . number_format($outstandingTermBalance, 2) . " outstanding but no payments created\n";
}
