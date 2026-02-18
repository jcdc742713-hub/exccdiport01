<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Admin-specific fields
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('terms_accepted_at')->nullable()->after('is_active');
            $table->json('permissions')->nullable()->after('terms_accepted_at');
            $table->string('department')->nullable()->after('permissions');
            $table->enum('admin_type', ['super', 'manager', 'operator'])->nullable()->after('department');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('admin_type');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            $table->timestamp('last_login_at')->nullable()->after('updated_by');

            // Indexing for performance
            $table->index('role');
            $table->index('is_active');
            $table->index('admin_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor('created_by');
            $table->dropForeignIdFor('updated_by');

            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['admin_type']);

            $table->dropColumn([
                'is_active',
                'terms_accepted_at',
                'permissions',
                'department',
                'admin_type',
                'created_by',
                'updated_by',
                'last_login_at',
            ]);
        });
    }
};
