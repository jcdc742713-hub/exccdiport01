<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payment_terms', function (Blueprint $table) {
            // Make due_date nullable to support payment terms without set due dates
            $table->date('due_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_payment_terms', function (Blueprint $table) {
            $table->date('due_date')->nullable(false)->change();
        });
    }
};
