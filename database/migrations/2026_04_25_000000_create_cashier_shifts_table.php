<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cashier_shifts', function (Blueprint $row) {
            $row->id();
            $row->foreignId('user_id')->constrained();
            $row->foreignId('shop_id')->constrained();
            $row->dateTime('start_time');
            $row->dateTime('end_time')->nullable();
            $row->decimal('starting_cash', 15, 2)->default(0);
            $row->decimal('total_sales', 15, 2)->default(0);
            $row->decimal('actual_cash', 15, 2)->nullable();
            $row->decimal('difference', 15, 2)->default(0);
            $row->string('status')->default('open'); // open, closed
            $row->text('note')->nullable();
            $row->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
