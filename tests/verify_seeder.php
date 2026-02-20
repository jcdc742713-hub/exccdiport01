<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use Illuminate\Support\Facades\DB;

echo "=== SEEDER VERIFICATION ===\n\n";

$student = Student::where('email', 'jcdc742713@gmail.com')->first();

if (!$student) {
    echo "❌ Student not found\n";
    exit(1);
}

echo "✓ Student: {$student->email} (ID: {$student->id})\n";

$assessment = $student->assessments()->first();
if (!$assessment) {
    echo "❌ No assessment found\n";
    exit(1);
}

echo "✓ Assessment: {$assessment->assessment_number}\n";
echo "  Total: $" . number_format($assessment->total_assessment, 2) . "\n\n";

$terms = $assessment->paymentTerms()->orderBy('term_order')->get();
echo "Payment Terms (" . $terms->count() . " total):\n";

$expectedPercentages = [1 => 42.15, 2 => 17.86, 3 => 17.86, 4 => 14.88, 5 => 7.26];
$totalAmount = 0;

foreach ($terms as $term) {
    $status = $term->status === 'paid' ? '✓ PAID' : '⏳ ' . strtoupper($term->status);
    echo "  {$term->term_order}. {$term->term_name}\n";
    echo "     Amount: $" . number_format($term->amount, 2) . " ({$term->percentage}%)\n";
    echo "     Status: {$status}\n";
    echo "     Due: {$term->due_date->format('M d, Y')}\n";
    $totalAmount += $term->amount;
}

echo "\n✓ Total Payment Terms Amount: $" . number_format($totalAmount, 2) . "\n";
echo "✓ Assessment Total: $" . number_format($assessment->total_assessment, 2) . "\n";

// Check transactions
$transactions = DB::table('transactions')
    ->where('user_id', $student->user_id)
    ->where('kind', 'payment')
    ->get();

echo "\nTransactions (" . $transactions->count() . " total):\n";
foreach ($transactions as $tx) {
    echo "  • {$tx->reference}: $" . number_format($tx->amount, 2) . " - {$tx->status}\n";
}

echo "\n============================================================\n";
echo "✅ SEEDER VERIFICATION COMPLETE\n";
echo "============================================================\n";
