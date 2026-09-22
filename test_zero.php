<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/transactions', 'GET', []);
$user = \App\Models\User::where('role', 'admin')->first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$query = \App\Models\Transaction::with(['items.product', 'shop', 'user'])->latest();

// Copy exactly from controller
if ($user->role === 'admin') {
    if ($request->has('shop_id') && $request->shop_id != '') {
        $query->where('shop_id', $request->shop_id);
    }
} else {
    $query->where('shop_id', $user->shop_id);
}

$summaryQuery = clone $query;
$total_sales = $summaryQuery->where('transactions.status', '!=', 'void')->sum('total_price');

var_dump($total_sales);
