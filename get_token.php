<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if ($user) {
    $token = $user->createToken('test-token')->plainTextToken;
    echo "TOKEN=" . $token . "\n";
    echo "SHOP_ID=" . $user->shop_id . "\n";
} else {
    echo "No user found.\n";
}
