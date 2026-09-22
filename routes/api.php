<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\CashierShiftController;
use App\Http\Controllers\Api\CourierController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-google', [AuthController::class, 'loginGoogle']);
Route::get('/app-version', function () {
    return response()->json([
        'version' => \App\Models\AppSetting::get('flutter_app_version', '1.0.1'),
        'download_url' => \App\Models\AppSetting::get('flutter_apk_url', url('/storage/app-release.apk')),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Kurir Stock Summary
    Route::get('/courier/stock-summary', [CourierController::class, 'stockSummary']);
    Route::post('/courier/plan-delivery/{shop_id}', [CourierController::class, 'planDelivery']);
    Route::post('/courier/complete-delivery/{shop_id}', [CourierController::class, 'completeDelivery']);
    Route::post('/courier/request-stock', [CourierController::class, 'requestStock']);
    Route::post('/courier/clear-request/{shop_id}', [CourierController::class, 'clearRequest']);
    
    // Shift Kasir
    Route::get('/cashier', [CashierShiftController::class, 'index']);
    Route::get('/cashier/current', [CashierShiftController::class, 'current']);
    Route::post('/cashier/open', [CashierShiftController::class, 'open']);
    Route::post('/cashier/close', [CashierShiftController::class, 'close']);
    Route::delete('/cashier/{id}', [CashierShiftController::class, 'destroy']); // Hapus Shift

    // Manajemen Toko & User
    Route::post('/shops/receive-stock', [ShopController::class, 'receiveStock']);
    Route::post('/shops/update-branch-stocks/{shop_id}', [ShopController::class, 'updateBranchStocks']);
    Route::post('/user/fcm-token', [UserController::class, 'updateFcmToken']);
    Route::apiResource('/users', UserController::class);
    Route::apiResource('/shops', ShopController::class);
    
    // Absensi
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/history', [AttendanceController::class, 'history']);
    Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy']); // Hapus Absensi

    // Keuangan
    Route::get('/finance/summary', [FinanceController::class, 'summary']);
    Route::get('/finance/chart', [FinanceController::class, 'chart']);
    Route::get('/finance', [FinanceController::class, 'index']);
    Route::post('/finance', [FinanceController::class, 'store']);
    Route::put('/finance/{id}', [FinanceController::class, 'update']);

    // Produk & Kategori
    Route::apiResource('/categories', CategoryController::class);
    Route::post('/products/{product}/stocks', [ProductController::class, 'updateStocks']);
    Route::apiResource('/products', ProductController::class);
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void']);
    Route::apiResource('/transactions', TransactionController::class);
    
    // Chat
    Route::get('/chat/unread-count', [\App\Http\Controllers\Api\ChatController::class, 'getUnreadCount']);
    Route::get('/chat/conversations', [\App\Http\Controllers\Api\ChatController::class, 'getConversations']);
    Route::get('/chat/messages/{shop_id?}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
    Route::post('/chat/read/{shop_id?}', [\App\Http\Controllers\Api\ChatController::class, 'markAsRead']);
});
