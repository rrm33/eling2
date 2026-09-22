<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!<br>";
} else {
    echo "OPcache is not enabled.<br>";
}

// Clear artisan caches if possible
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "Laravel cache cleared!<br>";
echo "Silakan coba kirim pesan chat lagi sekarang.";
