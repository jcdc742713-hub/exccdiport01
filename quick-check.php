<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$user = User::where('email', 'jcdc742713@gmail.com')->first();
$userId = $user->id;

echo "User ID: {$userId}\n";
echo "User Name: " . $user->name . "\n\n";

$terms = DB::table('student_payment_terms')->where('user_id', $userId)->orderBy('term_order')->get();
echo "Payment Terms Count: " . count($terms) . "\n";

foreach ($terms as $t) {
  echo "  Term {$t->term_order}: {$t->term_name} | Status: {$t->status} | Balance: {$t->balance} | Paid: {$t->paid_date}\n";
}

$transactions = DB::table('transactions')->where('user_id', $userId)->where('kind', 'payment')->orderByDesc('paid_at')->get();
echo "\nTransactions Count: " . count($transactions) . "\n";

foreach ($transactions as $tx) {
  echo "  {$tx->reference} | Amount: {$tx->amount} | Status: {$tx->status} | Paid: {$tx->paid_at}\n";
}

echo "\n";
