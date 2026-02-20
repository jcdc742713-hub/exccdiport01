<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Services\StudentPaymentService;
use Illuminate\Support\Facades\DB;

echo "=== PAYMENT FLOW TEST ===\n\n";

$user = User::where('email', 'jcdc742713@gmail.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

// Get assessment
$assessment = StudentAssessment::where('user_id', $user->id)->latest('created_at')->first();
if (!$assessment) {
    echo "❌ No assessment found\n";
    exit(1);
}

// Get payment terms
$terms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
    ->orderBy('term_order')
    ->get();

echo "👤 Student: {$user->email}\n";
echo "📋 Assessment: {$assessment->assessment_number} | Total: " . number_format($assessment->total_assessment, 2) . "\n\n";

echo "BEFORE PAYMENT:\n";
foreach ($terms as $term) {
    echo "  {$term->term_name}: " . number_format($term->balance, 2) . " | Status: {$term->status}\n";
}

$totalBefore = $terms->sum('balance');
echo "\nTotal Outstanding: " . number_format($totalBefore, 2) . "\n\n";

// Test payment to first term
$testTerm = $terms->first();
$paymentAmount = 2000; // Partial payment

echo "🧪 TEST: Paying " . number_format($paymentAmount, 2) . " to {$testTerm->term_name}\n";
echo "  Selected Term ID: {$testTerm->id}\n\n";

try {
    $service = new StudentPaymentService();
    
    $result = $service->processPayment($user, $paymentAmount, [
        'payment_method' => 'cash',
        'paid_at' => now(),
        'description' => 'Test payment',
        'selected_term_id' => $testTerm->id,
        'term_name' => $testTerm->term_name,
    ]);

    echo "✅ PAYMENT PROCESSED\n";
    echo "  Transaction: {$result['transaction_reference']}\n";
    echo "  Message: {$result['message']}\n\n";

    // Refresh terms from database
    $terms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
        ->orderBy('term_order')
        ->get();

    echo "AFTER PAYMENT:\n";
    foreach ($terms as $term) {
        $change = "";
        if ($term->id === $testTerm->id) {
            $change = " (Updated)";
        } elseif ($term->term_order > $testTerm->term_order && $term->balance != $term->amount) {
            $change = " (Carryover Applied)";
        }
        echo "  {$term->term_name}: " . number_format($term->balance, 2) . " | Status: {$term->status}{$change}\n";
    }

    $totalAfter = $terms->sum('balance');
    echo "\nTotal Outstanding: " . number_format($totalAfter, 2) . "\n";
    echo "Amount Paid: " . number_format($totalBefore - $totalAfter, 2) . "\n\n";

    // Verify calculation
    $expectedTotal = $totalBefore - $paymentAmount;
    if (round($totalAfter, 2) === round($expectedTotal, 2)) {
        echo "✅ Payment calculation verified\n";
    } else {
        echo "❌ Payment calculation mismatch\n";
        echo "   Expected: " . number_format($expectedTotal, 2) . "\n";
        echo "   Got: " . number_format($totalAfter, 2) . "\n";
    }

    // Check breakdown
    echo "\n📊 PAYMENT BREAKDOWN:\n";
    foreach ($result['payment_breakdown'] as $item) {
        if (isset($item['term_name'])) {
            echo "  • {$item['term_name']}: Applied " . number_format($item['amount_applied'], 2) . 
                 " | New Balance: " . number_format($item['new_balance'], 2) . "\n";
        } else if (isset($item['note'])) {
            echo "  • {$item['note']}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ PAYMENT FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
