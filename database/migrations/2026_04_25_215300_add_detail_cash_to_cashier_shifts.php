<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('cashier_shifts', 'total_income')) {
                $table->decimal('total_income', 15, 2)->default(0)->after('total_sales');
            }
            if (!Schema::hasColumn('cashier_shifts', 'total_expense')) {
                $table->decimal('total_expense', 15, 2)->default(0)->after('total_income');
            }
            if (!Schema::hasColumn('cashier_shifts', 'expected_balance')) {
                $table->decimal('expected_balance', 15, 2)->default(0)->after('total_expense');
            }
        });
    }

    public function down()
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['total_income', 'total_expense', 'expected_balance']);
        });
    }
};
