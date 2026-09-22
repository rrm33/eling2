<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $user = \App\Models\User::updateOrCreate(
        ['email' => 'kasir33@gmail.com'],
        [
            'name' => 'Kasir 33',
            'password' => \Illuminate\Support\Facades\Hash::make('11111111'),
            'role' => 'cashier',
            'shop_id' => 1
        ]
    );
    echo json_encode([
        'status' => 'Berhasil!',
        'pesan' => 'User kasir33@gmail.com berhasil dibuat.',
        'user' => $user
    ]);
} catch (\Throwable $e) {
    echo json_encode([
        'status' => 'Gagal / Error',
        'pesan_error' => $e->getMessage(),
        'file' => $e->getFile(),
        'baris' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
