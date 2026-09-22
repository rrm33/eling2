<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
echo \App\Models\User::get(['id', 'name', 'role'])->toJson(JSON_PRETTY_PRINT);
