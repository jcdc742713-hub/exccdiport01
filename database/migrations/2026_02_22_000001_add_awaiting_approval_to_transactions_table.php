<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'awaiting_approval' to the status enum in transactions table
        Schema::table('transactions', function (Blueprint $table) {
            // For MySQL, we need to change the enum to include the new value
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','failed','cancelled','awaiting_approval') DEFAULT 'pending'");
        });
    }

    public function down(): void
    {
        // Remove 'awaiting_approval' from the status enum
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','paid','failed','cancelled') DEFAULT 'pending'");
        });
    }
};
