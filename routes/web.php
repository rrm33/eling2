<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAdminController;

// Web Admin Authentication Routes
Route::get('/login', [WebAdminController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAdminController::class, 'login']);
Route::post('/logout', [WebAdminController::class, 'logout'])->name('logout');

// Web Admin Dashboard Routes (Authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/', [WebAdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin', [WebAdminController::class, 'dashboard']);
    Route::get('/admin/dashboard', [WebAdminController::class, 'dashboard']);
    Route::get('/admin/reports', [WebAdminController::class, 'reports'])->name('admin.reports');
    Route::get('/admin/reports/shop-chart', [WebAdminController::class, 'getShopChartData'])->name('admin.reports.shop-chart');

    // Transaksi
    Route::get('/admin/transactions', [WebAdminController::class, 'transactions'])->name('admin.transactions');
    Route::post('/admin/transactions/{id}/update', [WebAdminController::class, 'updateTransaction'])->name('admin.transactions.update');
    Route::post('/admin/transactions/{id}/void', [WebAdminController::class, 'voidTransaction'])->name('admin.transactions.void');
    Route::delete('/admin/transactions/{id}', [WebAdminController::class, 'deleteTransaction'])->name('admin.transactions.delete');

    // Produk & Kategori
    Route::get('/admin/products', [WebAdminController::class, 'products'])->name('admin.products');
    Route::post('/admin/products', [WebAdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::post('/admin/products/{id}/update', [WebAdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/admin/products/{id}', [WebAdminController::class, 'deleteProduct'])->name('admin.products.delete');
    
    Route::post('/admin/categories', [WebAdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::post('/admin/categories/{id}/update', [WebAdminController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/admin/categories/{id}', [WebAdminController::class, 'deleteCategory'])->name('admin.categories.delete');

    // Cabang / Toko
    Route::get('/admin/shops', [WebAdminController::class, 'shops'])->name('admin.shops');
    Route::post('/admin/shops', [WebAdminController::class, 'storeShop'])->name('admin.shops.store');
    Route::post('/admin/shops/{id}/update', [WebAdminController::class, 'updateShop'])->name('admin.shops.update');
    Route::delete('/admin/shops/{id}', [WebAdminController::class, 'deleteShop'])->name('admin.shops.delete');

    // User / Pengguna
    Route::get('/admin/users', [WebAdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/users', [WebAdminController::class, 'storeUser'])->name('admin.users.store');
    Route::post('/admin/users/{id}/update', [WebAdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [WebAdminController::class, 'deleteUser'])->name('admin.users.delete');

    // Keuangan & Kas
    Route::get('/admin/finance', [WebAdminController::class, 'finance'])->name('admin.finance');
    Route::post('/admin/finance', [WebAdminController::class, 'storeFinance'])->name('admin.finance.store');

    // Shift Kasir
    Route::get('/admin/shifts', [WebAdminController::class, 'shifts'])->name('admin.shifts');

    // Absensi
    Route::get('/admin/attendance', [WebAdminController::class, 'attendance'])->name('admin.attendance');
    
    // Chat
    Route::get('/admin/chat', [WebAdminController::class, 'chat'])->name('admin.chat');
    Route::post('/admin/chat/send', [WebAdminController::class, 'sendChatMessage'])->name('admin.chat.send');

    // Tagihan & Langganan (Billing)
    Route::get('/admin/billing', [WebAdminController::class, 'billing'])->name('admin.billing');
    Route::post('/admin/billing/status', [WebAdminController::class, 'updateBillingStatus'])->name('admin.billing.status');
    Route::post('/admin/settings/app-version', [WebAdminController::class, 'updateAppVersion'])->name('admin.settings.app-version');
    Route::get('/admin/billing/invoice/{year}/{month}', [WebAdminController::class, 'viewInvoice'])->name('admin.billing.invoice');
});

Route::get('/percobaan', function () {
    return view('percobaan');
});

Route::get('/setup-admin', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin System',
                'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
                'role' => 'admin',
                'shop_id' => null,
            ]
        );
        return response()->json([
            'status' => 'Berhasil!',
            'pesan' => 'Database berhasil di-migrate & Akun admin@admin.com (password: admin123) berhasil dibuat/diperbarui!',
            'user' => $user
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'Gagal / Error',
            'pesan_error' => $e->getMessage(),
        ]);
    }
});

Route::get('/setup-superadmin', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'superadmin@admin.com'],
            [
                'name' => 'Super Admin System',
                'password' => \Illuminate\Support\Facades\Hash::make('superadmin123'),
                'role' => 'super_admin',
                'shop_id' => null,
            ]
        );
        return response()->json([
            'status' => 'Berhasil!',
            'pesan' => 'Database berhasil di-migrate & Akun superadmin@admin.com (Role: Super Admin, password: superadmin123) berhasil dibuat!',
            'user' => $user
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'Gagal / Error',
            'pesan_error' => $e->getMessage(),
        ]);
    }
});

Route::get('/setup-storage-link', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return response()->json([
            'status' => 'Berhasil!',
            'pesan' => 'Storage link berhasil dibuat! Folder public/storage sudah terhubung ke storage/app/public.',
        ]);
    } catch (\Throwable $e) {
        try {
            $target = storage_path('app/public');
            $shortcut = public_path('storage');
            if (!file_exists($shortcut)) {
                symlink($target, $shortcut);
            }
            return response()->json([
                'status' => 'Berhasil (Manual Symlink)!',
                'pesan' => 'Folder public/storage berhasil dibuat via manual symlink!',
            ]);
        } catch (\Throwable $ex) {
            return response()->json([
                'status' => 'Gagal / Error',
                'pesan_error' => $ex->getMessage(),
            ]);
        }
    }
});

// Dynamic Storage File Serving Route (Guarantees image delivery on cPanel / public_html)
Route::get('/storage/{path}', function ($path) {
    $possiblePaths = [
        public_path('storage/products/' . $path),
        public_path('storage/' . $path),
        base_path('../public_html/storage/products/' . $path),
        base_path('../public_html/storage/' . $path),
        storage_path('app/public/products/' . $path),
        storage_path('app/public/' . $path),
        storage_path('app/products/' . $path),
        storage_path('app/' . $path),
    ];

    foreach ($possiblePaths as $filePath) {
        if (file_exists($filePath) && !is_dir($filePath)) {
            return response()->file($filePath, [
                'Cache-Control' => 'max-age=31536000, public',
            ]);
        }
    }

    abort(404);
})->where('path', '.*');
