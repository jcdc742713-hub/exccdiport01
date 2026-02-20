<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$user = User::where('email', 'jcdc742713@gmail.com')->first();
$userId = $user->id;

echo "Checking Assessment:\n";
echo "User ID: {$userId}\n\n";

$assessment = DB::table('student_assessments')->where('user_id', $userId)->orderByDesc('created_at')->first();

if ($assessment) {
    echo "Assessment Found:\n";
    echo "  ID: {$assessment->id}\n";
    echo "  Assessment #: {$assessment->assessment_number}\n";
    echo "  Total: {$assessment->total_assessment}\n";
    echo "  School Year: {$assessment->school_year}\n";
    echo "  Semester: {$assessment->semester}\n\n";
    
    $assessmentId = $assessment->id;
    
    $terms = DB::table('student_payment_terms')->where('student_assessment_id', $assessmentId)->orderBy('term_order')->get();
    echo "Payment Terms for this assessment: " . count($terms) . "\n";
    
    foreach ($terms as $t) {
        echo "  Term {$t->term_order}: {$t->term_name} | Status: {$t->status} | Balance: {$t->balance} | Amount: {$t->amount} | Paid: {$t->paid_date}\n";
    }
} else {
    echo "❌ No assessment found for this user\n";
}

echo "\n";
