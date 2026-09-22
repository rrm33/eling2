<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\DB::enableQueryLog();

$request = Illuminate\Http\Request::create('/api/transactions', 'GET', [
]);
$user = \App\Models\User::where('role', 'admin')->first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app()->make(\App\Http\Controllers\Api\TransactionController::class);
$response = $controller->index($request);

print_r(\DB::getQueryLog());
echo "\nResponse:\n";
echo $response->getContent();
