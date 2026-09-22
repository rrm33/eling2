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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->integer('stock')->default(0);
            $table->timestamps();
            
            $table->unique(['product_id', 'shop_id']); // Satu produk hanya punya satu baris stok per toko
        });

        // Migrate existing stock data
        \Illuminate\Support\Facades\DB::table('products')->orderBy('id')->chunk(100, function ($products) {
            foreach ($products as $product) {
                if ($product->shop_id) {
                    \Illuminate\Support\Facades\DB::table('product_stocks')->insert([
                        'product_id' => $product->id,
                        'shop_id' => $product->shop_id,
                        'stock' => $product->stock,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
