<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_id', 'user_id', 'invoice_number', 'subtotal', 'discount', 
        'tax', 'total_price', 'pay_amount', 'change_amount', 
        'payment_method', 'status', 'note', 'void_by', 'created_at'
    ];

    public function items()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::deleting(function ($transaction) {
            // Kembalikan stok jika status transaksi sebelumnya bukan 'void'
            if ($transaction->status !== 'void') {
                $shop = $transaction->shop;
                if ($shop) {
                    foreach ($transaction->items as $detail) {
                        $product = $detail->product;
                        if ($product) {
                            $isDimsum = ($product->bundle_qty > 0);

                            if ($isDimsum) {
                                $bundleQty = $product->bundle_qty ?? 1;
                                $shop->increment('stock', $detail->qty * $bundleQty);
                            } else {
                                $productStock = $product->stocks()->where('shop_id', $shop->id)->first();
                                if ($productStock) {
                                    $productStock->increment('stock', $detail->qty);
                                } else {
                                    $product->stocks()->create([
                                        'shop_id' => $shop->id,
                                        'stock' => $detail->qty
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        });

        static::updating(function ($transaction) {
            // Periksa apakah status berubah
            if ($transaction->isDirty('status')) {
                $oldStatus = $transaction->getOriginal('status');
                $newStatus = $transaction->status;

                // Jika berubah menjadi 'void' dari status sebelumnya yang bukan 'void'
                if ($newStatus === 'void' && $oldStatus !== 'void') {
                    $shop = $transaction->shop;
                    if ($shop) {
                        foreach ($transaction->items as $detail) {
                            $product = $detail->product;
                            if ($product) {
                                $isDimsum = ($product->bundle_qty > 0);

                                if ($isDimsum) {
                                    $bundleQty = $product->bundle_qty ?? 1;
                                    $shop->increment('stock', $detail->qty * $bundleQty);
                                } else {
                                    $productStock = $product->stocks()->where('shop_id', $shop->id)->first();
                                    if ($productStock) {
                                        $productStock->increment('stock', $detail->qty);
                                    } else {
                                        $product->stocks()->create([
                                            'shop_id' => $shop->id,
                                            'stock' => $detail->qty
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
                // Jika status berubah dari 'void' kembali menjadi aktif (bukan 'void')
                elseif ($oldStatus === 'void' && $newStatus !== 'void') {
                    $shop = $transaction->shop;
                    if ($shop) {
                        foreach ($transaction->items as $detail) {
                            $product = $detail->product;
                            if ($product) {
                                $isDimsum = ($product->bundle_qty > 0);

                                if ($isDimsum) {
                                    $bundleQty = $product->bundle_qty ?? 1;
                                    $shop->decrement('stock', $detail->qty * $bundleQty);
                                } else {
                                    $productStock = $product->stocks()->where('shop_id', $shop->id)->first();
                                    if ($productStock) {
                                        $productStock->decrement('stock', $detail->qty);
                                    } else {
                                        $product->stocks()->create([
                                            'shop_id' => $shop->id,
                                            'stock' => -$detail->qty
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
