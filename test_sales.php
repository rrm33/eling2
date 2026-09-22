<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::table('transactions')
    ->where('status', '!=', 'void')
    ->select(
        DB::raw("SUM(CASE WHEN LOWER(IFNULL(payment_method, 'cash')) IN ('cash', 'tunai') THEN total_price ELSE 0 END) as total_cash"),
        DB::raw("SUM(CASE WHEN LOWER(IFNULL(payment_method, 'cash')) = 'qris' THEN total_price ELSE 0 END) as total_qris"),
        DB::raw("SUM(CASE WHEN LOWER(IFNULL(payment_method, 'cash')) NOT IN ('cash', 'tunai', 'qris') THEN total_price ELSE 0 END) as total_lainnya")
    )->first();
print_r($res);
