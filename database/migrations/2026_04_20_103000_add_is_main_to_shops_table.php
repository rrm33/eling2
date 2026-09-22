<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('is_main')->default(false)->after('name');
        });

        // Set toko pertama sebagai pusat secara otomatis jika ada data
        $firstShop = DB::table('shops')->first();
        if ($firstShop) {
            DB::table('shops')->where('id', $firstShop->id)->update(['is_main' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('is_main');
        });
    }
};
