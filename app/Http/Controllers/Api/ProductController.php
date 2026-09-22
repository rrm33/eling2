<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $user = $request->user();
        $products = $query->latest()->get();

        // Timpa nilai stock global dengan stock khusus cabang ini (jika user memiliki shop_id, berlaku untuk kasir & admin cabang)
        $products->map(function ($product) use ($user) {
            if ($user->shop_id) {
                $productStock = $product->stocks()->where('shop_id', $user->shop_id)->first();
                $product->stock = $productStock ? $productStock->stock : 0;
            }
            if ($user->role === 'admin') {
                $product->load('stocks');
            }
            return $product;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'cost_price' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'parent_id' => 'nullable|integer|exists:products,id',
            'bundle_qty' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = basename($path);
        }

        $product = Product::create($data);

        // Buat stok awal untuk cabang yang membuat produk ini atau dari input admin
        if ($request->has('branch_stocks') && is_array($request->branch_stocks)) {
            foreach ($request->branch_stocks as $shop_id => $stock) {
                $product->stocks()->updateOrCreate(
                    ['shop_id' => $shop_id],
                    ['stock' => $stock]
                );
            }
        } elseif ($request->user()->shop_id) {
            $product->stocks()->create([
                'shop_id' => $request->user()->shop_id,
                'stock' => $request->input('stock', 0) // Gunakan input dari flutter jika ada
            ]);
        }

        return response()->json(['message' => 'Produk berhasil ditambahkan', 'data' => $product], 201);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'parent_id' => 'nullable|integer|exists:products,id',
            'bundle_qty' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'status' => 'nullable|in:0,1',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            // Hapus foto lama
            if ($product->image) {
                Storage::disk('public')->delete('products/' . $product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = basename($path);
        }

        $product->update($data);

        // Update stok untuk cabang ini atau dari input admin
        if ($request->has('branch_stocks') && is_array($request->branch_stocks)) {
            foreach ($request->branch_stocks as $shop_id => $stock) {
                $product->stocks()->updateOrCreate(
                    ['shop_id' => $shop_id],
                    ['stock' => $stock]
                );
            }
        } elseif ($request->user()->shop_id && $request->has('stock')) {
            // Update stok khusus untuk produk lain (bukan dimsum) dari flutter
            $product->stocks()->updateOrCreate(
                ['shop_id' => $request->user()->shop_id],
                ['stock' => $request->input('stock')]
            );
        }

        return response()->json(['message' => 'Produk berhasil diperbarui', 'data' => $product]);
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete('products/' . $product->image);
        }
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }

    public function updateStocks(Request $request, Product $product)
    {
        $request->validate([
            'branch_stocks' => 'required|array',
        ]);

        foreach ($request->branch_stocks as $shop_id => $stock) {
            $product->stocks()->updateOrCreate(
                ['shop_id' => $shop_id],
                ['stock' => $stock]
            );
        }

        return response()->json(['message' => 'Stok produk di cabang berhasil diperbarui']);
    }
}
