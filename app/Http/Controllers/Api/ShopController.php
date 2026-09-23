<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    public function index()
    {
        return response()->json(Shop::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'logo' => 'nullable|image|max:2048', // Max 2MB
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // Jika ditandai sebagai pusat, matikan pusat lainnya dulu
        if ($request->is_main) {
            Shop::where('is_main', true)->update(['is_main' => false]);
        }

        $shop = Shop::create($data);

        // Otomatis tambahkan semua produk ke toko baru dengan stok 0
        $products = \App\Models\Product::all();
        foreach ($products as $product) {
            \App\Models\ProductStock::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'stock' => 0,
                'planned_stock' => 0,
                'requested_stock' => 0,
            ]);
        }

        return response()->json(['message' => 'Toko berhasil ditambahkan.', 'data' => $shop], 201);
    }

    public function update(Request $request, Shop $shop)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'logo' => 'nullable|image|max:2048',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($shop->logo) {
                Storage::disk('public')->delete($shop->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // Jika cabang ini diubah jadi pusat, matikan pusat lainnya dulu
        if ($request->is_main) {
            Shop::where('is_main', true)->update(['is_main' => false]);
        }

        $shop->update($data);

        return response()->json(['message' => 'Toko berhasil diperbarui.', 'data' => $shop]);
    }

    public function destroy(Shop $shop)
    {
        if ($shop->is_main) {
            return response()->json(['message' => 'Toko Pusat tidak bisa dihapus.'], 422);
        }

        // Lepas relasi akun staff/kasir dari cabang ini agar tidak terkunci
        $shop->users()->update(['shop_id' => null]);

        // Hapus data stok cabang terkait
        $shop->productStocks()->delete();

        if ($shop->logo) {
            Storage::disk('public')->delete($shop->logo);
        }

        $shop->delete();
        return response()->json(['message' => 'Toko berhasil dihapus.']);
    }

    /**
     * Kasir Menerima Barang (Stok Masuk ke Cabang)
     */
    public function receiveStock(Request $request)
    {
        $user = $request->user();
        $shopId = $user->shop_id;

        if (!$shopId) {
            return response()->json(['message' => 'User tidak terikat dengan cabang mana pun.'], 422);
        }

        $shop = Shop::with('productStocks')->find($shopId);
        if (!$shop) {
            return response()->json(['message' => 'Cabang tidak ditemukan.'], 404);
        }

        // Hitung total planned stock (dimsum utama + produk non-varian)
        $mainPlanned = (int)($shop->planned_stock ?? 0);
        $productsPlanned = (int)$shop->productStocks->sum('planned_stock');
        $totalPlanned = $mainPlanned + $productsPlanned;

        if ($totalPlanned <= 0) {
            return response()->json(['message' => 'Tidak ada kiriman barang yang perlu diterima.'], 422);
        }

        // Proses penerimaan dimsum utama
        if ($mainPlanned > 0) {
            $shop->stock += $mainPlanned;
            $shop->planned_stock = 0;
            $shop->requested_stock = 0;
        }

        // Proses penerimaan produk non-varian
        foreach ($shop->productStocks as $ps) {
            if ($ps->planned_stock > 0) {
                $ps->update([
                    'stock' => $ps->stock + $ps->planned_stock,
                    'planned_stock' => 0,
                    'requested_stock' => 0
                ]);
            }
        }

        $shop->save();

        return response()->json([
            'message' => "Berhasil menerima kiriman barang ($totalPlanned unit).",
            'stock' => $shop->stock
        ]);
    }

    public function updateBranchStocks(Request $request, $shop_id)
    {
        $request->validate([
            'stocks' => 'required|array',
        ]);

        $shop = Shop::find($shop_id);
        if (!$shop) {
            return response()->json(['message' => 'Cabang tidak ditemukan.'], 404);
        }

        foreach ($request->stocks as $product_id => $stockVal) {
            $pId = (int)$product_id;
            $stock = (int)$stockVal;

            if ($pId === 0) {
                // Update stock toko (dimsum global)
                $shop->update(['stock' => $stock]);
            } else {
                // Update stock produk non-varian
                \App\Models\ProductStock::updateOrCreate(
                    ['shop_id' => $shop_id, 'product_id' => $pId],
                    ['stock' => $stock]
                );
            }
        }

        return response()->json(['message' => 'Stok cabang berhasil diperbarui.']);
    }
}
