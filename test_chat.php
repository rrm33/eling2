<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::first(); // Ambil user pertama
$request = Illuminate\Http\Request::create('/api/chat/messages', 'POST', [
    'message' => 'Hello from script',
    'shop_id' => $user->shop_id ?? 1
]);
$request->setUserResolver(function () use ($user) {
    return $user;
});

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
