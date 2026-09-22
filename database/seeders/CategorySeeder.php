<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Dimsum Kukus', 'Dimsum Goreng', 'Minuman', 'Paket Hemat'];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['name' => $cat]);
        }
    }
}
