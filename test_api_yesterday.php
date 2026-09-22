<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/transactions', 'GET', [
    'start_date' => '2026-08-06',
    'end_date' => '2026-08-06',
]);

// Auth mock
$user = \App\Models\User::where('role', 'admin')->first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app()->make(\App\Http\Controllers\Api\TransactionController::class);
$response = $controller->index($request);
echo $response->getContent();
