<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING STUDENT PAYMENT ISSUE ===\n\n";

$user = \App\Models\User::where('email', 'jcdc742713@gmail.com')->first();
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}
echo "✅ User found: {$user->name}\n";
echo "   ID: {$user->id}\n";
echo "   Role: " . $user->role->value . "\n";
echo "   Has Student: " . ($user->student ? 'Yes' : 'No') . "\n\n";

$assessment = \App\Models\StudentAssessment::where('user_id', $user->id)->latest()->first();
if (!$assessment) {
    echo "❌ No assessment found for student\n";
    exit(1);
} else {
    echo "✅ Assessment found:\n";
    echo "   ID: {$assessment->id}\n";
    echo "   Status: {$assessment->status}\n\n";
}

$terms = \App\Models\StudentPaymentTerm::where('student_assessment_id', $assessment->id)->get();
echo "✅ Payment Terms: " . count($terms) . "\n";
if ($terms->isEmpty()) {
    echo "   ❌ NO PAYMENT TERMS FOUND!\n";
    exit(1);
}
foreach ($terms as $term) {
    echo "   - {$term->term_name}: ₱" . number_format($term->amount, 2) . " (Balance: ₱" . number_format($term->balance, 2) . ")\n";
}
echo "\n";

$workflow = \App\Models\Workflow::where('type', 'payment_approval')->where('is_active', true)->first();
if (!$workflow) {
    echo "❌ Payment Approval Workflow NOT FOUND\n";
    exit(1);
}
echo "✅ Payment Approval Workflow found (ID: {$workflow->id})\n";
echo "   Steps: " . count($workflow->steps) . "\n";
foreach ($workflow->steps as $index => $step) {
    echo "   Step " . ($index + 1) . ": {$step['name']} (requires_approval: " . ($step['requires_approval'] ? 'Yes' : 'No') . ")\n";
}
echo "\n";

// Test payment submission
echo "=== TESTING PAYMENT SUBMISSION ===\n\n";
try {
    $paymentService = new \App\Services\StudentPaymentService();
    $firstTerm = $terms->first();
    
    $result = $paymentService->processPayment($user, 1000, [
        'payment_method' => 'gcash',
        'paid_at' => now()->toDateString(),
        'selected_term_id' => $firstTerm->id,
        'term_name' => $firstTerm->term_name,
        'description' => 'Test Payment'
    ], true); // $requiresApproval = true
    
    echo "✅ Payment processed successfully!\n";
    echo "   Transaction ID: {$result['transaction_id']}\n";
    echo "   Reference: {$result['transaction_reference']}\n";
    echo "   Requires Approval: " . ($result['requires_approval'] ? 'Yes' : 'No') . "\n";
    echo "   Workflow Instance ID: {$result['workflow_instance_id']}\n";
    echo "   Message: {$result['message']}\n";
    
    // Check workflow approvals
    $instance = \App\Models\WorkflowInstance::find($result['workflow_instance_id']);
    $approvals = \App\Models\WorkflowApproval::where('workflow_instance_id', $instance->id)->get();
    echo "\n   Workflow Approvals Created: " . count($approvals) . "\n";
    foreach ($approvals as $approval) {
        echo "   - Step: {$approval->step_name}, Approver ID: {$approval->approver_id}, Status: {$approval->status}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Payment processing failed:\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "   Trace: {$e->getTraceAsString()}\n";
    exit(1);
}
