<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/api/transactions', 'GET', [
    'start_date' => '2025-01-01', // Example past date to match 'kemarin'
    'end_date' => '2026-12-31',
]);

// Auth mock
$user = \App\Models\User::where('role', 'admin')->first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app()->make(\App\Http\Controllers\Api\TransactionController::class);
$response = $controller->index($request);
echo $response->getContent();
