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
            // Safely drop columns (FK constraints will be handled automatically)
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->dropColumn('terms_accepted_at');
            }
            if (Schema::hasColumn('users', 'permissions')) {
                $table->dropColumn('permissions');
            }
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            if (Schema::hasColumn('users', 'admin_type')) {
                $table->dropColumn('admin_type');
            }
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('users', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};
