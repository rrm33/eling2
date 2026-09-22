<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourierController extends Controller
{
    public function stockSummary(Request $request)
    {
        $query = Shop::query();

        if ($request->user()->role === 'cashier' && $request->user()->shop_id) {
            $query->where('id', $request->user()->shop_id);
        }

        $allShops = $query->get();
        
        // Ambil semua produk non-varian
        $nonVariantProducts = Product::where('bundle_qty', 0)->get();

        $shopsDetail = $allShops->map(function($shop) use ($nonVariantProducts) {
            // Ambil stok produk yang sudah ada di database untuk toko ini
            $existingStocks = \App\Models\ProductStock::where('shop_id', $shop->id)
                ->get()
                ->keyBy('product_id');

            // Gabungkan agar semua produk non-varian tampil di semua toko
            $products = $nonVariantProducts->map(function($product) use ($existingStocks) {
                $ps = $existingStocks->get($product->id);
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $ps ? (int)$ps->stock : 0,
                    'planned_stock' => $ps ? (int)$ps->planned_stock : 0,
                    'requested_stock' => $ps ? (int)$ps->requested_stock : 0,
                ];
            })->values()->all();

            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'main_stock_name' => 'Produk Utama Dimsum',
                'stock' => (int)($shop->stock ?? 0),
                'planned_stock' => (int)($shop->planned_stock ?? 0),
                'requested_stock' => (int)($shop->requested_stock ?? 0),
                'min_stock' => (int)($shop->min_stock ?? 100),
                'products' => $products
            ];
        });

        // Ringkasan Global (Sum both main stock and all product stock planned values)
        $totalStock = $allShops->sum('stock');
        
        $totalShopPlanned = $allShops->sum('planned_stock');
        $totalProductsPlanned = \App\Models\ProductStock::whereIn('shop_id', $allShops->pluck('id'))->sum('planned_stock');
        $totalPlanned = $totalShopPlanned + $totalProductsPlanned;

        $lowStockShopsCount = $allShops->filter(function($s) {
            return $s->stock < ($s->min_stock ?? 100);
        })->count();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_shops' => $allShops->count(),
                'total_items_to_prepare' => $totalPlanned,
                'low_stock_shops_count' => $lowStockShopsCount,
                'global_stock' => $totalStock
            ],
            'shops_detail' => $shopsDetail,
            'items_to_prepare' => [
                [
                    'name' => 'Produk Utama Dimsum',
                    'total_needed' => $totalShopPlanned,
                    'shops_low_stock' => $lowStockShopsCount
                ]
            ]
        ]);
    }

    public function planDelivery(Request $request, $shop_id)
    {
        $request->validate([
            'planned_stocks' => 'required|array',
        ]);

        $shop = Shop::find($shop_id);
        if ($shop) {
            foreach ($request->planned_stocks as $product_id => $qty) {
                $pId = (int)$product_id;
                $qtyVal = (int)($qty ?? 0);

                if ($pId === 0) {
                    // Update planned_stock toko (dimsum global)
                    $shop->update(['planned_stock' => $qtyVal]);
                } else {
                    // Update planned_stock produk non-varian
                    \App\Models\ProductStock::updateOrCreate(
                        ['shop_id' => $shop_id, 'product_id' => $pId],
                        ['planned_stock' => $qtyVal]
                    );
                }
            }
        }

        return response()->json(['message' => 'Rencana pengiriman berhasil disimpan ke Toko.']);
    }

    public function completeDelivery(Request $request, $shop_id)
    {
        $shop = Shop::with('productStocks')->find($shop_id);
        if ($shop) {
            if ($shop->planned_stock > 0) {
                $shop->update([
                    'stock' => $shop->stock + $shop->planned_stock,
                    'planned_stock' => 0,
                    'requested_stock' => 0
                ]);
            }
            
            // Selesaikan pengiriman untuk produk non-varian
            foreach ($shop->productStocks as $ps) {
                if ($ps->planned_stock > 0) {
                    $ps->update([
                        'stock' => $ps->stock + $ps->planned_stock,
                        'planned_stock' => 0,
                        'requested_stock' => 0
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Pengiriman selesai. Stok Toko telah bertambah.']);
    }

    public function requestStock(Request $request)
    {
        $request->validate([
            'requested_stocks' => 'required|array',
        ]);

        $user = $request->user();
        $shopId = $user->shop_id;

        if (!$shopId) {
            return response()->json(['message' => 'User tidak terikat dengan cabang mana pun.'], 422);
        }

        $shop = Shop::find($shopId);
        if ($shop) {
            foreach ($request->requested_stocks as $product_id => $qty) {
                $pId = (int)$product_id;
                $qtyVal = (int)($qty ?? 0);

                if ($pId === 0) {
                    // Update requested_stock toko (dimsum global)
                    $shop->update(['requested_stock' => $qtyVal]);
                } else {
                    // Update requested_stock produk non-varian
                    \App\Models\ProductStock::updateOrCreate(
                        ['shop_id' => $shopId, 'product_id' => $pId],
                        ['requested_stock' => $qtyVal]
                    );
                }
            }
        }

        return response()->json(['message' => 'Pengajuan stok berhasil dikirim.']);
    }

    public function clearRequest(Request $request, $shop_id)
    {
        $shop = Shop::with('productStocks')->find($shop_id);
        if ($shop) {
            $shop->update(['requested_stock' => 0]);
            
            foreach ($shop->productStocks as $ps) {
                $ps->update(['requested_stock' => 0]);
            }
        }

        return response()->json(['message' => 'Pengajuan stok berhasil dihapus.']);
    }
}
