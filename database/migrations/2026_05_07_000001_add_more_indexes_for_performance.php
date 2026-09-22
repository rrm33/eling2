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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('shop_id');
        });

        Schema::table('finances', function (Blueprint $table) {
            $table->index('shop_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('shop_id');
            $table->index('category_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('shop_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['shop_id']);
        });

        Schema::table('finances', function (Blueprint $table) {
            $table->dropIndex(['shop_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['shop_id']);
            $table->dropIndex(['category_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['shop_id']);
            $table->dropIndex(['user_id']);
        });
    }
};
