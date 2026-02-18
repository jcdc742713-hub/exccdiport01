<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\StudentAssessment;
use App\Models\StudentPaymentTerm;
use App\Services\StudentPaymentService;

echo "=== CARRYOVER PAYMENT TEST ===\n\n";

$user = User::where('email', 'jcdc742713@gmail.com')->first();
$assessment = StudentAssessment::where('user_id', $user->id)->latest('created_at')->first();
$terms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
    ->orderBy('term_order')
    ->get();

echo "Current Balances:\n";
foreach ($terms as $term) {
    echo "  {$term->term_name}: " . number_format($term->balance, 2) . " | Status: {$term->status}\n";
}

$totalBefore = $terms->sum('balance');
echo "\nTotal Outstanding: " . number_format($totalBefore, 2) . "\n\n";

// Test payment larger than first term (should cause carryover)
$firstTerm = $terms->first();
$paymentAmount = 7000; // More than first term

echo "🧪 TEST: Paying " . number_format($paymentAmount, 2) . " to {$firstTerm->term_name}\n";
echo "  Selected Term: {$firstTerm->term_name} (Balance: " . number_format($firstTerm->balance, 2) . ")\n";
echo "  Expected: Pays off {$firstTerm->term_name}, carries over to next terms\n\n";

try {
    $service = new StudentPaymentService();
    
    $result = $service->processPayment($user, $paymentAmount, [
        'payment_method' => 'cash',
        'paid_at' => now(),
        'selected_term_id' => $firstTerm->id,
    ]);

    echo "✅ PAYMENT PROCESSED\n";
    echo "  Transaction: {$result['transaction_reference']}\n";
    echo "  Message: {$result['message']}\n\n";

    // Refresh terms
    $terms = StudentPaymentTerm::where('student_assessment_id', $assessment->id)
        ->orderBy('term_order')
        ->get();

    echo "AFTER CARRYOVER PAYMENT:\n";
    foreach ($terms as $term) {
        $icon = $term->status === 'paid' ? '✓' : ($term->status === 'partial' ? '◐' : '○');
        echo "  {$icon} {$term->term_name}: " . number_format($term->balance, 2) . " | Status: {$term->status}\n";
    }

    $totalAfter = $terms->sum('balance');
    echo "\nTotal Outstanding: " . number_format($totalAfter, 2) . "\n";
    echo "Amount Paid: " . number_format($totalBefore - $totalAfter, 2) . "\n\n";

    // Show breakdown
    echo "📊 PAYMENT BREAKDOWN:\n";
    foreach ($result['payment_breakdown'] as $item) {
        if (isset($item['term_name'])) {
            $status = $item['status'] === 'paid' ? '(PAID)' : '(PARTIAL)';
            echo "  • {$item['term_name']}: Applied " . number_format($item['amount_applied'], 2) . 
                 " | Balance: " . number_format($item['new_balance'], 2) . " {$status}\n";
        } else if (isset($item['overpayment'])) {
            echo "  • Overpayment: " . number_format($item['overpayment'], 2) . "\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ PAYMENT FAILED: " . $e->getMessage() . "\n";
}
