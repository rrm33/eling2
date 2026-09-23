<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Finance;
use App\Models\Attendance;
use App\Models\CashierShift;
use App\Models\Message;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class WebAdminController extends Controller
{
    // ==========================================
    // AUTHENTICATION
    // ==========================================

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $fieldType = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if (Auth::attempt([$fieldType => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            if ($user->role !== 'admin') {
                // If user is not admin, still allow login or warn
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Email/Username atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ==========================================
    // DASHBOARD
    // ==========================================

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $shopId = $request->shop_id;

        $query = Transaction::with(['items.product', 'shop', 'user'])->latest();

        if ($shopId) {
            $query->where('shop_id', $shopId);
        } elseif ($user->role !== 'admin' && $user->shop_id) {
            $query->where('shop_id', $user->shop_id);
        }

        // Tanggal default hari ini
        $todayStr = Carbon::now()->toDateString();
        $todayQuery = (clone $query)->whereDate('created_at', $todayStr);

        $total_sales = (clone $todayQuery)->where('status', '!=', 'void')->sum('total_price');
        $total_transactions = (clone $todayQuery)->where('status', '!=', 'void')->count();

        $total_sales_cash = (clone $todayQuery)->where('status', '!=', 'void')
            ->where(function($q) {
                $q->where('payment_method', 'Tunai')->orWhere('payment_method', 'cash')->orWhere('payment_method', 'tunai')->orWhereNull('payment_method');
            })->sum('total_price');

        $total_sales_qris = (clone $todayQuery)->where('status', '!=', 'void')
            ->where(function($q) {
                $q->where('payment_method', 'QRIS')->orWhere('payment_method', 'qris');
            })->sum('total_price');

        $total_sales_lainnya = (clone $todayQuery)->where('status', '!=', 'void')
            ->where(function($q) {
                $q->whereNotIn('payment_method', ['Tunai', 'cash', 'tunai', 'QRIS', 'qris'])
                  ->whereNotNull('payment_method');
            })->sum('total_price');

        // Total Dimsum (Butir) & Saus Bangkok Hari Ini
        $todayTxIds = (clone $todayQuery)->pluck('id');

        $total_grains = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', $todayTxIds)
            ->sum(DB::raw('transaction_details.qty * IFNULL(products.bundle_qty, 1)'));

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
            ->whereIn('transactions.id', $todayTxIds)
            ->sum('transaction_details.qty');

        // Rincian Produk Terjual Hari Ini
        $product_details = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', '!=', 'void')
            ->whereIn('transactions.id', $todayTxIds)
            ->select('products.name', 'products.image', DB::raw('SUM(transaction_details.qty) as total_qty'), DB::raw('SUM(transaction_details.subtotal) as total_omset'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_qty')
            ->take(10)
            ->get();

        // Stats Cabang
        $shop_stats = DB::table('transactions')
            ->join('shops', 'transactions.shop_id', '=', 'shops.id')
            ->where('transactions.status', '!=', 'void')
            ->whereDate('transactions.created_at', $todayStr)
            ->select(
                'shops.id as shop_id',
                'shops.name as shop_name',
                DB::raw('SUM(transactions.total_price) as total_sales'),
                DB::raw('COUNT(transactions.id) as total_tx')
            )
            ->groupBy('shops.id', 'shops.name')
            ->orderByDesc('total_sales')
            ->get();

        // 10 Transaksi Terakhir
        $latest_transactions = (clone $query)->take(10)->get();

        $shops = Shop::orderBy('name')->get();

        return view('admin.dashboard', compact(
            'total_sales', 'total_sales_cash', 'total_sales_qris', 'total_sales_lainnya',
            'total_transactions', 'total_grains', 'total_saus', 'product_details',
            'shop_stats', 'latest_transactions', 'shops', 'shopId'
        ));
    }

    // ==========================================
    // LAPORAN PENJUALAN (REPORTS)
    // ==========================================

    public function reports(Request $request)
    {
        try {
            $user = Auth::user();
            $shopId = $request->shop_id;
            $dateFilter = $request->get('date_filter', 'month');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $selectedYear = (int) $request->get('year', Carbon::now()->year);
            $selectedMonth = (int) $request->get('month', Carbon::now()->month);

            $query = Transaction::query()->where('status', '!=', 'void');

            if ($shopId) {
                $query->where('shop_id', $shopId);
            } elseif ($user && $user->role !== 'admin' && $user->role !== 'super_admin' && $user->shop_id) {
                $query->where('shop_id', $user->shop_id);
            }

            // Apply Date Filtering
            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
                if ($endDate) {
                    $query->whereDate('created_at', '<=', $endDate);
                    $filterLabel = Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
                } else {
                    $filterLabel = 'Mulai ' . Carbon::parse($startDate)->format('d M Y');
                }
            } elseif ($dateFilter === '7days') {
                $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
                $filterLabel = '7 Hari Terakhir';
            } elseif ($dateFilter === 'month') {
                $query->whereYear('created_at', $selectedYear)->whereMonth('created_at', $selectedMonth);
                $filterLabel = 'Bulan ' . Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->isoFormat('MMMM Y');
            } elseif ($dateFilter === 'year') {
                $query->whereYear('created_at', $selectedYear);
                $filterLabel = 'Tahun ' . $selectedYear;
            } elseif ($dateFilter === 'all') {
                $filterLabel = 'Semua Waktu';
            } else { // today
                $query->whereDate('created_at', Carbon::today());
                $filterLabel = 'Hari Ini (' . Carbon::today()->format('d M Y') . ')';
            }

            $txIds = (clone $query)->pluck('id');

            // Summary Calculations
            $total_sales = (clone $query)->sum('total_price') ?: 0;
            $total_transactions = (clone $query)->count();

            $total_sales_cash = (clone $query)->where(function($q) {
                $q->where('payment_method', 'Tunai')->orWhere('payment_method', 'cash')->orWhere('payment_method', 'tunai')->orWhereNull('payment_method');
            })->sum('total_price') ?: 0;

            $total_sales_qris = (clone $query)->where(function($q) {
                $q->where('payment_method', 'QRIS')->orWhere('payment_method', 'qris');
            })->sum('total_price') ?: 0;

            $total_sales_lainnya = (clone $query)->where(function($q) {
                $q->whereNotIn('payment_method', ['Tunai', 'cash', 'tunai', 'QRIS', 'qris'])->whereNotNull('payment_method');
            })->sum('total_price') ?: 0;

            // Raw Material & Product Breakdown
            if ($txIds->isNotEmpty()) {
                $total_grains = DB::table('transactions')
                    ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                    ->join('products', 'transaction_details.product_id', '=', 'products.id')
                    ->whereIn('transactions.id', $txIds)
                    ->sum(DB::raw('transaction_details.qty * IFNULL(products.bundle_qty, 1)')) ?: 0;

                $total_saus = DB::table('transactions')
                    ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                    ->join('products', 'transaction_details.product_id', '=', 'products.id')
                    ->where(function($q) {
                        $q->where('products.name', 'like', '%saus%')
                          ->orWhere('products.name', 'like', '%saos%')
                          ->orWhere('products.name', 'like', '%bangkok%');
                    })
                    ->whereIn('transactions.id', $txIds)
                    ->sum('transaction_details.qty') ?: 0;

                $product_details = DB::table('transactions')
                    ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                    ->join('products', 'transaction_details.product_id', '=', 'products.id')
                    ->whereIn('transactions.id', $txIds)
                    ->select('products.id', 'products.name', 'products.image', DB::raw('SUM(transaction_details.qty) as total_qty'), DB::raw('SUM(transaction_details.subtotal) as total_omset'))
                    ->groupBy('products.id', 'products.name', 'products.image')
                    ->orderByDesc('total_qty')
                    ->get();

                $shop_sales = DB::table('transactions')
                    ->whereIn('id', $txIds)
                    ->select('shop_id', DB::raw('SUM(total_price) as total_sales'))
                    ->groupBy('shop_id')
                    ->pluck('total_sales', 'shop_id');

                $shop_stats = DB::table('transactions')
                    ->join('shops', 'transactions.shop_id', '=', 'shops.id')
                    ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
                    ->join('products', 'transaction_details.product_id', '=', 'products.id')
                    ->whereIn('transactions.id', $txIds)
                    ->select(
                        'shops.id as shop_id',
                        'shops.name as shop_name',
                        DB::raw('SUM(transaction_details.qty * IFNULL(products.bundle_qty, 1)) as total_dimsum'),
                        DB::raw("SUM(CASE WHEN products.name LIKE '%saus%' OR products.name LIKE '%saos%' THEN transaction_details.qty ELSE 0 END) as total_saus")
                    )
                    ->groupBy('shops.id', 'shops.name')
                    ->get()
                    ->map(function($s) use ($shop_sales) {
                        $s->total_sales = $shop_sales[$s->shop_id] ?? 0;
                        return $s;
                    })
                    ->sortByDesc('total_sales')
                    ->values();

                $chartData = DB::table('transactions')
                    ->whereIn('id', $txIds)
                    ->select(DB::raw('DATE(created_at) as date_group'), DB::raw('SUM(total_price) as daily_sales'))
                    ->groupBy('date_group')
                    ->orderBy('date_group', 'asc')
                    ->get();
            } else {
                $total_grains = 0;
                $total_saus = 0;
                $product_details = collect();
                $shop_stats = collect();
                $chartData = collect();
            }

            // Finance Summary (Income & Expense)
            $financeQuery = Finance::query()->where('status', 'active');
            if ($shopId) {
                $financeQuery->where('shop_id', $shopId);
            }
            if ($startDate) {
                $financeQuery->whereDate('date', '>=', $startDate);
                if ($endDate) $financeQuery->whereDate('date', '<=', $endDate);
            } elseif ($dateFilter === '7days') {
                $financeQuery->whereDate('date', '>=', Carbon::now()->subDays(7));
            } elseif ($dateFilter === 'month') {
                $financeQuery->whereYear('date', $selectedYear)->whereMonth('date', $selectedMonth);
            } elseif ($dateFilter === 'year') {
                $financeQuery->whereYear('date', $selectedYear);
            } elseif ($dateFilter === 'today') {
                $financeQuery->whereDate('date', Carbon::today());
            }

            $total_income = (clone $financeQuery)->where('type', 'income')->sum('amount') ?: 0;
            $total_expense = (clone $financeQuery)->where('type', 'expense')->sum('amount') ?: 0;
            $net_profit = ($total_sales + $total_income) - $total_expense;

            $shops = Shop::orderBy('name')->get();

            return view('admin.reports.index', compact(
                'total_sales', 'total_transactions', 'total_sales_cash', 'total_sales_qris', 'total_sales_lainnya',
                'total_grains', 'total_saus', 'total_income', 'total_expense', 'net_profit',
                'product_details', 'shop_stats', 'chartData', 'shops', 'shopId', 'dateFilter',
                'startDate', 'endDate', 'selectedYear', 'selectedMonth', 'filterLabel'
            ));
        } catch (\Throwable $e) {
            \Log::error('Error in WebAdminController@reports: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memuat laporan: ' . $e->getMessage());
        }
    }

    // ==========================================
    // TRANSACTIONS MANAGEMENT
    // ==========================================

    public function transactions(Request $request)
    {
        $user = Auth::user();
        $query = Transaction::with(['items.product', 'shop', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        } elseif ($user->role !== 'admin' && $user->shop_id) {
            $query->where('shop_id', $user->shop_id);
        }

        // Filter Tanggal
        $dateFilter = $request->get('date_filter', 'today');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($dateFilter === 'today' && !$startDate) {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }
        } elseif ($dateFilter === '7days') {
            $query->whereDate('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($dateFilter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }

        $summaryQuery = clone $query;
        $total_sales = (clone $summaryQuery)->where('status', '!=', 'void')->sum('total_price');
        $total_count = (clone $summaryQuery)->where('status', '!=', 'void')->count();

        $transactions = $query->paginate(20)->withQueryString();
        $shops = Shop::orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.transactions.index', compact('transactions', 'shops', 'products', 'total_sales', 'total_count', 'dateFilter', 'startDate', 'endDate'));
    }

    public function updateTransaction(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array',
            'total_price' => 'required|numeric',
            'pay_amount' => 'required|numeric',
            'created_at' => 'required|date',
        ]);

        $transaction = Transaction::findOrFail($id);

        try {
            DB::transaction(function () use ($request, $transaction) {
                // 1. Rollback stok lama jika bukan void
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

                // 2. Hapus detail lama
                $transaction->items()->delete();

                // 3. Update transaksi utama
                $transaction->update([
                    'subtotal' => $request->subtotal ?? $request->total_price,
                    'total_price' => $request->total_price,
                    'pay_amount' => $request->pay_amount,
                    'change_amount' => $request->change_amount ?? ($request->pay_amount - $request->total_price),
                    'payment_method' => $request->payment_method ?? $transaction->payment_method,
                    'status' => $request->status ?? $transaction->status,
                    'note' => $request->note ?? $transaction->note,
                    'created_at' => Carbon::parse($request->created_at),
                ]);

                // 4. Buat detail baru & potong stok
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $qty = intval($item['quantity'] ?? $item['qty'] ?? 1);
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

            return back()->with('success', 'Transaksi berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    public function voidTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        if ($transaction->status === 'void') {
            return back()->with('error', 'Transaksi ini sudah dibatalkan sebelumnya.');
        }

        $transaction->update([
            'status' => 'void',
            'void_by' => Auth::user()->name,
        ]);

        return back()->with('success', 'Transaksi berhasil dibatalkan (void).');
    }

    public function deleteTransaction($id)
    {
        if (Auth::user()->role !== 'admin') {
            return back()->with('error', 'Hanya admin yang dapat menghapus transaksi.');
        }

        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }

    // ==========================================
    // PRODUCTS & CATEGORIES MANAGEMENT
    // ==========================================

    public function products(Request $request)
    {
        $products = Product::with(['category', 'stocks.shop'])->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $shops = Shop::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories', 'shops'));
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'bundle_qty' => $request->bundle_qty ?? 0,
            'stock' => $request->stock ?? 0,
            'image' => $imagePath,
            'status' => $request->status ?? 'active',
        ]);

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'bundle_qty' => $request->bundle_qty ?? 0,
            'status' => $request->status ?? $product->status,
        ];

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Category::create(['name' => $request->name]);
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $request->validate(['name' => 'required|string|max:255']);
        $category->update(['name' => $request->name]);
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    // ==========================================
    // SHOPS (CABANG) MANAGEMENT
    // ==========================================

    public function shops()
    {
        $shops = Shop::withCount('users')->orderBy('name')->get();
        return view('admin.shops.index', compact('shops'));
    }

    public function storeShop(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        Shop::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'stock' => $request->stock ?? 0,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status ?? 'active',
        ]);

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function updateShop(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ]);

        $shop->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'stock' => $request->stock ?? $shop->stock,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->status ?? $shop->status,
        ]);

        return back()->with('success', 'Cabang berhasil diperbarui.');
    }

    public function deleteShop($id)
    {
        $shop = Shop::findOrFail($id);
        $shop->delete();
        return back()->with('success', 'Cabang berhasil dihapus.');
    }

    // ==========================================
    // USERS MANAGEMENT
    // ==========================================

    public function users()
    {
        $currentUser = Auth::user();
        $query = User::with('shop')->orderBy('name');

        // Jika bukan super_admin, sembunyikan semua user dengan role super_admin!
        if ($currentUser->role !== 'super_admin') {
            $query->where('role', '!=', 'super_admin');
        }

        $users = $query->get();
        $shops = Shop::orderBy('name')->get();
        return view('admin.users.index', compact('users', 'shops'));
    }

    public function storeUser(Request $request)
    {
        $currentUser = Auth::user();
        
        $validRoles = ['admin', 'cashier', 'courier'];
        if ($currentUser->role === 'super_admin') {
            $validRoles[] = 'super_admin';
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:' . implode(',', $validRoles),
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'shop_id' => $request->shop_id,
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $currentUser = Auth::user();
        $targetUser = User::findOrFail($id);

        if ($targetUser->role === 'super_admin' && $currentUser->role !== 'super_admin') {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengelola akun Super Admin.');
        }

        $validRoles = ['admin', 'cashier', 'courier'];
        if ($currentUser->role === 'super_admin') {
            $validRoles[] = 'super_admin';
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:' . implode(',', $validRoles),
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'shop_id' => $request->shop_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $targetUser->update($data);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function deleteUser($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::findOrFail($id);

        if ($targetUser->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($targetUser->role === 'super_admin' && $currentUser->role !== 'super_admin') {
            return back()->with('error', 'Anda tidak memiliki akses untuk menghapus akun Super Admin.');
        }

        $targetUser->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    // ==========================================
    // FINANCE & EXPENSES MANAGEMENT
    // ==========================================

    public function finance(Request $request)
    {
        $user = Auth::user();
        $query = Finance::with(['user', 'shop'])->latest();

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        } elseif ($user->role !== 'admin' && $user->shop_id) {
            $query->where('shop_id', $user->shop_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $finances = $query->paginate(20)->withQueryString();
        $shops = Shop::orderBy('name')->get();

        // Financial Summary
        $todayStr = Carbon::now()->toDateString();
        $totalSalesToday = Transaction::where('status', '!=', 'void')
            ->whereDate('created_at', $todayStr)
            ->sum('total_price');

        $totalIncome = Finance::where('type', 'income')->where('status', 'active')->sum('amount');
        $totalExpense = Finance::where('type', 'expense')->where('status', 'active')->sum('amount');
        $balance = ($totalSalesToday + $totalIncome) - $totalExpense;

        return view('admin.finance.index', compact('finances', 'shops', 'totalSalesToday', 'totalIncome', 'totalExpense', 'balance'));
    }

    public function storeFinance(Request $request)
    {
        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric',
            'category' => 'required|string',
            'note' => 'required|string',
        ]);

        Finance::create([
            'shop_id' => $request->shop_id ?? Auth::user()->shop_id,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'amount' => $request->amount,
            'category' => $request->category,
            'note' => $request->note,
            'date' => $request->date ?? now()->toDateString(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Data keuangan berhasil dicatat.');
    }

    // ==========================================
    // CASHIER SHIFTS
    // ==========================================

    public function shifts(Request $request)
    {
        $query = CashierShift::with(['user', 'shop'])->latest();

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        $shifts = $query->paginate(20)->withQueryString();
        $shops = Shop::orderBy('name')->get();

        return view('admin.shifts.index', compact('shifts', 'shops'));
    }

    // ==========================================
    // ATTENDANCE LOGS
    // ==========================================

    public function attendance(Request $request)
    {
        $query = Attendance::with(['user', 'shop'])->latest();

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $attendances = $query->paginate(20)->withQueryString();
        $shops = Shop::orderBy('name')->get();

        return view('admin.attendance.index', compact('attendances', 'shops'));
    }

    // ==========================================
    // CHAT WITH BRANCHES
    // ==========================================

    public function chat(Request $request)
    {
        $shops = Shop::orderBy('name')->get();
        $selectedShopId = $request->get('shop_id', $shops->first()?->id);
        
        $messages = collect();
        if ($selectedShopId) {
            $messages = Message::with('user')
                ->where('shop_id', $selectedShopId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mark as read
            Message::where('shop_id', $selectedShopId)
                ->where('user_id', '!=', Auth::id())
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
        }

        return view('admin.chat.index', compact('shops', 'selectedShopId', 'messages'));
    }

    public function sendChatMessage(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'message' => 'required|string',
        ]);

        Message::create([
            'user_id' => Auth::id(),
            'shop_id' => $request->shop_id,
            'message' => $request->message,
            'is_read' => 0,
        ]);

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    // ==========================================
    // APP BILLING & INVOICES (TAGIHAN APLIKASI)
    // ==========================================

    public function billing()
    {
        $monthlyFee = 100000;
        
        // Cari bulan pertama transaksi / sistem mulai berjalan
        $firstTransaction = Transaction::orderBy('created_at', 'asc')->first();
        $startCarbon = $firstTransaction && $firstTransaction->created_at 
            ? Carbon::parse($firstTransaction->created_at)->startOfMonth() 
            : Carbon::now()->startOfMonth();

        $nowCarbon = Carbon::now()->startOfMonth();

        $invoices = [];
        $current = clone $startCarbon;

        while ($current->lte($nowCarbon)) {
            $year = $current->year;
            $month = $current->month;
            $monthName = $current->locale('id')->isoFormat('MMMM Y');
            $lastDayOfMonth = (clone $current)->endOfMonth();

            // Tanggal tagihan (muncul di akhir bulan)
            $invoiceDate = $lastDayOfMonth->format('d M Y');
            $invoiceNumber = sprintf("INV/POS/%04d/%02d/001", $year, $month);

            // Biaya langganan: Bulan pertama Rp 100.000, bulan-bulan berikutnya Rp 80.000
            $amount = $current->isSameMonth($startCarbon) ? 100000 : 80000;

            // Cek apakah status di-override di database AppSetting
            $savedStatus = AppSetting::get("billing_status_{$year}_{$month}");

            if ($savedStatus) {
                $status = $savedStatus;
            } else {
                $isCurrentMonth = $current->isSameMonth($nowCarbon);
                if ($isCurrentMonth) {
                    $status = Carbon::now()->greaterThan($lastDayOfMonth) ? 'Jatuh Tempo' : 'Belum Jatuh Tempo';
                } else {
                    $status = 'Lunas';
                }
            }

            $invoices[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $monthName,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'amount' => $amount,
                'status' => $status,
            ];

            $current->addMonth();
        }

        // Tampilkan dari bulan terbaru di atas
        $invoices = array_reverse($invoices);

        $appVersion = AppSetting::get('flutter_app_version', '1.0.1');
        $apkUrl = AppSetting::get('flutter_apk_url', url('/storage/app-release.apk'));

        return view('admin.billing.index', compact('invoices', 'monthlyFee', 'appVersion', 'apkUrl'));
    }

    public function updateBillingStatus(Request $request)
    {
        $request->validate([
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'status' => 'required|in:Lunas,Belum Dibayar,Belum Jatuh Tempo,Jatuh Tempo',
        ]);

        $key = "billing_status_{$request->year}_{$request->month}";
        AppSetting::set($key, $request->status);

        return back()->with('success', "Status tagihan bulan {$request->month}/{$request->year} berhasil diubah menjadi: {$request->status}!");
    }

    public function updateAppVersion(Request $request)
    {
        $request->validate([
            'flutter_app_version' => 'required|string',
            'flutter_apk_url' => 'nullable|string',
            'apk_file' => 'nullable|file|max:102400',
        ]);

        AppSetting::set('flutter_app_version', $request->flutter_app_version);

        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext !== 'apk') {
                return back()->with('error', 'File yang diunggah harus berformat .apk');
            }

            $cleanVersion = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->flutter_app_version);
            $filename = 'app-release-v' . $cleanVersion . '.apk';

            // Save inside storage/app/public/apks
            $file->storeAs('apks', $filename, 'public');

            $apkUrl = asset('storage/apks/' . $filename);
            AppSetting::set('flutter_apk_url', $apkUrl);

            return back()->with('success', "File APK '{$filename}' berhasil diunggah & dipublikasikan! Versi acuan diperbarui ke {$request->flutter_app_version}.");
        } elseif ($request->filled('flutter_apk_url')) {
            AppSetting::set('flutter_apk_url', $request->flutter_apk_url);
        }

        return back()->with('success', "Versi acuan aplikasi Flutter berhasil diperbarui menjadi {$request->flutter_app_version}!");
    }

    public function viewInvoice($year, $month)
    {
        $firstTransaction = Transaction::orderBy('created_at', 'asc')->first();
        $startCarbon = $firstTransaction && $firstTransaction->created_at 
            ? Carbon::parse($firstTransaction->created_at)->startOfMonth() 
            : Carbon::now()->startOfMonth();

        $dateCarbon = Carbon::createFromDate($year, $month, 1);
        $monthlyFee = $dateCarbon->isSameMonth($startCarbon) ? 100000 : 80000;
        $monthName = $dateCarbon->locale('id')->isoFormat('MMMM Y');
        $lastDayOfMonthObj = (clone $dateCarbon)->endOfMonth();
        $lastDayOfMonth = $lastDayOfMonthObj->format('d M Y');
        $invoiceNumber = sprintf("INV/POS/%04d/%02d/001", $year, $month);
        
        $savedStatus = AppSetting::get("billing_status_{$year}_{$month}");

        if ($savedStatus) {
            $status = $savedStatus;
        } else {
            $nowCarbon = Carbon::now()->startOfMonth();
            $isCurrentMonth = $dateCarbon->isSameMonth($nowCarbon);
            if ($isCurrentMonth) {
                $status = Carbon::now()->greaterThan($lastDayOfMonthObj) ? 'Jatuh Tempo' : 'Belum Jatuh Tempo';
            } else {
                $status = 'Lunas';
            }
        }

        return view('admin.billing.invoice', compact(
            'year', 'month', 'monthName', 'lastDayOfMonth', 'invoiceNumber', 'monthlyFee', 'status'
        ));
    }

    public function getShopChartData(Request $request)
    {
        try {
            $shopId = $request->shop_id;
            $shop = Shop::findOrFail($shopId);

            $year = (int) $request->get('year', Carbon::now()->year);
            $month = (int) $request->get('month', Carbon::now()->month);

            $dateCarbon = Carbon::createFromDate($year, $month, 1);
            $daysInMonth = $dateCarbon->daysInMonth;

            $startDate = $dateCarbon->startOfMonth()->toDateString();
            $endDate = (clone $dateCarbon)->endOfMonth()->toDateString();

            $dailySalesRaw = DB::table('transactions')
                ->where('shop_id', $shopId)
                ->where('status', '!=', 'void')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->select(DB::raw('DATE(created_at) as date_group'), DB::raw('SUM(total_price) as daily_sales'))
                ->groupBy('date_group')
                ->pluck('daily_sales', 'date_group');

            $labels = [];
            $salesData = [];
            $totalSales = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
                $dayLabel = Carbon::createFromDate($year, $month, $day)->format('d M');

                $val = (float) ($dailySalesRaw[$currentDate] ?? 0);
                $labels[] = $dayLabel;
                $salesData[] = $val;
                $totalSales += $val;
            }

            return response()->json([
                'success' => true,
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'year' => $year,
                'month' => $month,
                'month_label' => $dateCarbon->locale('id')->isoFormat('MMMM Y'),
                'total_sales' => $totalSales,
                'total_sales_formatted' => 'Rp ' . number_format($totalSales, 0, ',', '.'),
                'labels' => $labels,
                'sales_data' => $salesData,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
