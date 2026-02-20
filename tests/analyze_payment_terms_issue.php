<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get user ID dynamically
$userId = DB::table('users')
    ->where('email', 'jcdc742713@gmail.com')
    ->value('id');

if (!$userId) {
    echo "Student not found!\n";
    exit;
}

// Get the assessment
$assessment = DB::table('student_assessments')
    ->where('user_id', $userId)
    ->first();

echo "=== ASSESSMENT ===\n";
echo "Total Assessment: $" . number_format($assessment->total_assessment, 2) . "\n\n";

// Get payment terms
$terms = DB::table('student_payment_terms')
    ->where('user_id', $userId)
    ->orderBy('term_order')
    ->get();

echo "=== PAYMENT TERMS ===\n";
$totalOriginal = 0;
$totalBalance = 0;
$totalPaidAmount = 0;

foreach ($terms as $term) {
    echo "Term: {$term->term_name}\n";
    echo "  Original Amount: $" . number_format($term->amount, 2) . "\n";
    echo "  Current Balance: $" . number_format($term->balance, 2) . "\n";
    echo "  Status: {$term->status}\n";
    echo "  Amount Paid (Original - Balance): $" . number_format($term->amount - $term->balance, 2) . "\n\n";
    
    $totalOriginal += $term->amount;
    $totalBalance += $term->balance;
    $totalPaidAmount += ($term->amount - $term->balance);
}

echo "=== TOTALS ===\n";
echo "Sum of Original Term Amounts: $" . number_format($totalOriginal, 2) . "\n";
echo "Total Assessment: $" . number_format($assessment->total_assessment, 2) . "\n";
echo "DISCREPANCY: $" . number_format($totalOriginal - $assessment->total_assessment, 2) . "\n\n";

echo "Sum of Current Balances: $" . number_format($totalBalance, 2) . "\n";
echo "Total Amount Paid (sum of individual paid amounts): $" . number_format($totalPaidAmount, 2) . "\n\n";

echo "=== CALCULATION METHODS ===\n";
echo "Method 1: Assessment - Total Balance = $" . number_format($assessment->total_assessment - $totalBalance, 2) . "\n";
echo "Method 2: Sum of Original - Sum of Balances = $" . number_format($totalOriginal - $totalBalance, 2) . "\n";
echo "Method 3: Sum of Individual Paid Amounts = $" . number_format($totalPaidAmount, 2) . "\n\n";

echo "=== ISSUE ===\n";
echo "The -1.54 likely appears because:\n";
echo "  - Assessment amount: $" . number_format($assessment->total_assessment, 2) . "\n";
echo "  - Sum of term amounts: $" . number_format($totalOriginal, 2) . "\n";
echo "  - Difference: $" . number_format($totalOriginal - $assessment->total_assessment, 2) . "\n";
echo "  - When calculating totalPaid as (Assessment - Balance), we get: $" . number_format($assessment->total_assessment - $totalBalance, 2) . "\n";
echo "  - But the actual sum of paid should be: $" . number_format($totalPaidAmount, 2) . "\n";
echo "  - This creates a $" . number_format(($totalPaidAmount) - ($assessment->total_assessment - $totalBalance), 2) . " discrepancy\n";
