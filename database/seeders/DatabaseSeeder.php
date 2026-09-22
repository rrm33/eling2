<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat Toko Pertama
        $shop = Shop::create([
            'name' => 'Dimsum Pusat',
            'address' => 'Jl. Jember No. 1',
            'latitude' => '-8.1724',
            'longitude' => '113.7003',
        ]);

        // Buat Admin Pertama
        User::create([
            'name' => 'Owner Dimsum',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'shop_id' => $shop->id,
        ]);

        // Buat Kasir Contoh
        User::create([
            'name' => 'Kasir Cabang',
            'email' => 'kasir@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'shop_id' => $shop->id,
        ]);
    }
}
