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
        // Untuk Finances: Tambah Status dan Void_By
        Schema::table('finances', function (Blueprint $table) {
            if (!Schema::hasColumn('finances', 'status')) {
                $table->string('status')->default('active')->after('amount');
            }
            if (!Schema::hasColumn('finances', 'void_by')) {
                $table->string('void_by')->nullable()->after('status');
            }
        });

        // Untuk Transactions: Hanya tambah Void_By (karena status sudah ada)
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'void_by')) {
                $table->string('void_by')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finances', function (Blueprint $table) {
            $table->dropColumn(['status', 'void_by']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['void_by']);
        });
    }
};
