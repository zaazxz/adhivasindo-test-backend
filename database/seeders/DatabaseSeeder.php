<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        // Customer user
        User::create([
            'name' => 'Customer',
            'email' => 'customer@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
        ]);

        // Sample products
        Product::create([
            'name' => 'Indomie Goreng',
            'desc' => 'Mie instan goreng favorit semua orang',
            'price' => 3500,
            'stock' => 100,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Teh Botol Sosro',
            'desc' => 'Teh dalam kemasan botol 350ml',
            'price' => 5000,
            'stock' => 50,
            'status' => 'inactive',
        ]);

        Product::create([
            'name' => 'Kopi Kapal Api',
            'desc' => 'Kopi sachet siap seduh',
            'price' => 2000,
            'stock' => 0,
            'status' => 'out-of-stock',
        ]);

        Product::create([
            'name' => 'Produk Draft Testing',
            'desc' => 'Produk ini belum dipublish, buat testing visibility',
            'price' => 10000,
            'stock' => 20,
            'status' => 'draft',
        ]);
    }
}
