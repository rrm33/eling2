<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    use HasFactory;

    // Pastikan mengarah ke nama tabel Anda yang sudah ada
    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id', 'product_id', 'qty', 'price', 'subtotal'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Otomatisasi Stok Backend
     */
    protected static function booted()
    {
        static::created(function ($detail) {
            $transaction = $detail->transaction;
            $product = $detail->product;

            // Ensure stock is sufficient before deduction
            if ($transaction && $product && $transaction->status !== 'void') {
                $shop = $transaction->shop;
                if ($shop) {
                    $isDimsum = ($product->bundle_qty > 0);
                    $bundleQty = $product->bundle_qty ?? 1;
                    $totalDeduct = $detail->qty * $bundleQty;

                    if ($isDimsum) {
                        // Bypassed check for dimsum, allow stock to go negative but still record/decrement it
                        $shop->decrement('stock', $totalDeduct);
                    } else {
                        // Bypassed check for non-dimsum as well
                        $productStock = $product->stocks()->where('shop_id', $shop->id)->first();
                        if ($productStock) {
                            $productStock->decrement('stock', $detail->qty);
                        } else {
                            // No stock record yet, create with negative
                            $product->stocks()->create([
                                'shop_id' => $shop->id,
                                'stock' => -$detail->qty,
                            ]);
                        }
                    }
                }
            }
        });
    }
}
