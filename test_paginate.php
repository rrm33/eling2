<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = \App\Models\Transaction::with(['items.product', 'shop', 'user'])->latest();

// Hitung Breakdown Pembayaran
$payment_breakdown = (clone $query)
    ->where('transactions.status', '!=', 'void')
    ->select(
        DB::raw("SUM(total_price) as total_cash")
    )->first();

$data = $query->paginate(15);
var_dump(count($data->items()));
