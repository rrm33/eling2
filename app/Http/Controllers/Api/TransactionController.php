<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Transaction::with(['items.product', 'shop', 'user'])->latest();

        // Hanya tampilkan yang tidak dibatalkan atau sesuai permintaan
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter Cabang (Hanya untuk Admin)
        if ($user->role === 'admin') {
            if ($request->has('shop_id') && $request->shop_id != '') {
                $query->where('shop_id', $request->shop_id);
            }
        } else {
            $query->where('shop_id', $user->shop_id);
        }

        // Filter Tanggal
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Clone query untuk menghitung summary secara efisien di SQL
        $summaryQuery = clone $query;
        $total_sales = $summaryQuery->where('transactions.status', '!=', 'void')->sum('total_price');
        $total_items = (clone $query)->where('transactions.status', '!=', 'void')->count();

        $total_sales_cash = (clone $query)->where('transactions.status', '!=', 'void')
            ->where(function($q) {
                $q->where('payment_method', 'Tunai')->orWhere('payment_method', 'cash')->orWhere('payment_method', 'tunai')->orWhereNull('payment_method');
            })->sum('total_price');

        $total_sales_qris = (clone $query)->where('transactions.status', '!=', 'void')
            ->where(function($q) {
                $q->where('payment_method', 'QRIS')->orWhere('payment_method', 'qris');
            })->sum('total_price');

        $total_sales_lainnya = (clone $query)->where('transactions.status', '!=', 'void')
            ->where(function($q) {
                $q->whereNotIn('payment_method', ['Tunai', 'cash', 'tunai', 'QRIS', 'qris'])
                  ->whereNotNull('payment_method');
            })->sum('total_price');

        // Hitung Total Butir (Raw Material Usage)
        $total_grains = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->sum(DB::raw('transaction_details.qty * IFNULL(products.bundle_qty, 1)'));

        // Hitung Total Saus Bangkok
        $total_saus = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', '!=', 'void')
            ->where(function($q) {
                $q->where('products.name', 'like', '%saus bangkok%')
                  ->orWhere('products.name', 'like', '%saos bangkok%')
                  ->orWhere('products.name', 'like', '%sauce bangkok%')
                  ->orWhere('products.name', 'like', '%bangkok%');
            })
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->sum('transaction_details.qty');

        // Hitung Total Produk Terjual (Total Qty)
        $total_products = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->sum('transaction_details.qty');

        // Ambil Rincian Produk Terjual
        $product_details = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->select('products.name', 'products.image', DB::raw('SUM(transaction_details.qty) as total_qty'), DB::raw('SUM(transaction_details.subtotal) as total_omset'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->get();

        // Hitung Dimsum & Saus Per-Cabang
        $shop_items = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->join('shops', 'transactions.shop_id', '=', 'shops.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->select(
                'shops.id as shop_id',
                'shops.name as shop_name',
                DB::raw('SUM(CASE WHEN products.bundle_qty > 0 THEN transaction_details.qty * products.bundle_qty ELSE 0 END) as total_dimsum'),
                DB::raw('SUM(CASE WHEN (products.name LIKE \'%saus bangkok%\' OR products.name LIKE \'%saos bangkok%\' OR products.name LIKE \'%sauce bangkok%\' OR products.name LIKE \'%bangkok%\') THEN transaction_details.qty ELSE 0 END) as total_saus')
            )
            ->groupBy('shops.id', 'shops.name')
            ->get();

        $shop_sales = DB::table('transactions')
            ->join('shops', 'transactions.shop_id', '=', 'shops.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->select(
                'shops.id as shop_id',
                DB::raw('SUM(transactions.total_price) as total_sales')
            )
            ->groupBy('shops.id')
            ->get()
            ->keyBy('shop_id');

        $shop_product_details = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->join('shops', 'transactions.shop_id', '=', 'shops.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', (clone $query)->pluck('id'))
            ->select(
                'shops.id as shop_id',
                'products.name as product_name',
                DB::raw('SUM(transaction_details.qty) as total_qty')
            )
            ->groupBy('shops.id', 'products.name')
            ->get()
            ->groupBy('shop_id');

        $shop_stats = $shop_items->map(function ($item) use ($shop_sales, $shop_product_details) {
            $item->total_sales = isset($shop_sales[$item->shop_id]) ? (double)$shop_sales[$item->shop_id]->total_sales : 0;
            $item->products = isset($shop_product_details[$item->shop_id]) ? $shop_product_details[$item->shop_id] : [];
            return $item;
        })->sortByDesc('total_sales')->values();

        // Ambil data (50 terbaru jika tanpa filter, atau 500 jika dengan filter tanggal/toko)
        $limit = ($request->has('start_date') || $request->has('end_date') || $request->has('shop_id')) ? 500 : 50;
        $transactions = $query->take($limit)->get();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'total_sales' => (double)$total_sales,
                'total_sales_cash' => (double)$total_sales_cash,
                'total_sales_qris' => (double)$total_sales_qris,
                'total_sales_lainnya' => (double)$total_sales_lainnya,
                'total_transactions' => $total_items,
                'total_grains' => (int)$total_grains,
                'total_saus' => (int)$total_saus,
                'total_products' => (int)$total_products,
                'product_details' => $product_details,
            ],
            'shop_stats' => $shop_stats,
            'data' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Input
        $request->validate([
            'items' => 'required|array',
            'total_price' => 'required',
            'pay_amount' => 'required',
            'payment_method' => 'required',
        ]);

        // 2. Tentukan ID Toko (Prioritaskan dari HP, fallback ke User)
        $targetShopId = $request->shop_id ?? $user->shop_id;

        if (!$targetShopId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal: Transaksi tidak memiliki ID Toko. Harap login ulang di aplikasi.'
            ], 422);
        }

        // 3. Tentukan Nomor Invoice
        $invoiceNumber = $request->invoice_number;

        // --- ANTI DUPLIKAT (Idempotency Check) ---
        if ($invoiceNumber) {
            $existing = Transaction::where('invoice_number', $invoiceNumber)->first();
            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaksi sudah tersimpan sebelumnya (Duplikat dicegah)',
                    'data' => $existing->load('items.product')
                ], 200);
            }
        }

        if (!$invoiceNumber) {
            $datePrefix = Carbon::now()->format('Ymd');
            $timePrefix = Carbon::now()->format('His');
            $invoiceNumber = "$datePrefix/{$targetShopId}/$timePrefix";
        }

        try {
            // Wrap the whole operation in a DB transaction to ensure atomicity
            $transaction = DB::transaction(function () use ($request, $user, $targetShopId, $invoiceNumber) {
                // 4. Simpan Transaksi Utama
                $transaction = Transaction::create([
                    'shop_id' => $targetShopId,
                    'user_id' => $user->id,
                    'invoice_number' => $invoiceNumber,
                    'subtotal' => $request->subtotal ?? $request->total_price,
                    'discount' => $request->discount ?? 0,
                    'tax' => $request->tax ?? 0,
                    'total_price' => $request->total_price,
                    'pay_amount' => $request->pay_amount,
                    'change_amount' => $request->change_amount ?? 0,
                    'payment_method' => $request->payment_method,
                    'status' => $request->status ?? 'completed',
                    'note' => $request->note,
                    'void_by' => $request->void_by,
                    'created_at' => $request->created_at ? Carbon::parse($request->created_at) : now(),
                ]);

                // 5. Simpan Detail & Hitung Total Unit Dimsum
                $totalUnits = 0;
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        TransactionDetail::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $product->id,
                            'qty' => $item['quantity'],
                            'price' => $product->price,
                            'subtotal' => $product->price * $item['quantity'],
                        ]);

                        // Ambil info bundle (berapa butir per porsi)
                        $bundleQty = $product->bundle_qty ?? 1;
                        $totalUnits += ($item['quantity'] * $bundleQty);
                    }
                }

                return $transaction;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Catch stock insufficient exception thrown from observer
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            // Generic error handling
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        // 6. Return Response (Stok dipotong otomatis oleh Model Observer)
        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil disimpan',
            'data' => $transaction->load('items.product')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'total_price' => 'required',
            'pay_amount' => 'required',
        ]);

        $transaction = Transaction::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $transaction) {
                // 1. KEMBALIKAN STOK LAMA (Jika status sebelumnya bukan void)
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
                                    }
                                }
                            }
                        }
                    }
                }

                // 2. HAPUS DETAIL TRANSAKSI LAMA (Penghapusan langsung di database agar tidak memicu event observer)
                $transaction->items()->delete();

                // 3. UPDATE TRANSAKSI UTAMA
                $transaction->update([
                    'subtotal' => $request->subtotal ?? $request->total_price,
                    'total_price' => $request->total_price,
                    'pay_amount' => $request->pay_amount,
                    'change_amount' => $request->change_amount ?? ($request->pay_amount - $request->total_price),
                    'payment_method' => $request->payment_method ?? $transaction->payment_method,
                    'status' => $request->status ?? $transaction->status,
                    'note' => $request->note ?? $transaction->note,
                    'created_at' => $request->created_at ? Carbon::parse($request->created_at) : $transaction->created_at,
                ]);

                // 4. BUAT DETAIL TRANSAKSI BARU & POTONG STOK BARU (Melalui model agar memicu observer created)
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $qty = intval($item['qty'] ?? $item['quantity'] ?? 1);
                        TransactionDetail::create([
                            'transaction_id' => $transaction->id,
                            'product_id' => $product->id,
                            'qty' => $qty,
                            'price' => $product->price,
                            'subtotal' => $product->price * $qty,
                        ]);
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil diperbarui.',
                'data' => $transaction->load('items.product')
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui transaksi (Masalah stok/koneksi): ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya admin yang dapat menghapus transaksi.'
            ], 403);
        }

        try {
            $transaction = Transaction::findOrFail($id);
            $transaction->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dihapus dan stok telah dikembalikan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function void(Request $request, $id)
    {
        $user = $request->user();

        try {
            $transaction = Transaction::findOrFail($id);
            if ($transaction->status === 'void') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Transaksi ini sudah dibatalkan sebelumnya.'
                ], 422);
            }

            $transaction->update([
                'status' => 'void',
                'void_by' => $user->name,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.',
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }
}
