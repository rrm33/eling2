<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$dates = DB::table('transactions')->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as cnt'))->groupBy('date')->get();
print_r($dates);
