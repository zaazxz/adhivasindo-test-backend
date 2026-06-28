<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // USERS
        // =========================

        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'created_at' => now(),
        ]);

        User::create([
            'name' => 'Customer',
            'email' => 'customer@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'customer',
            'created_at' => now(),
        ]);

        // =========================
        // PRODUCT TYPES (10 DATA)
        // =========================

        $types = [
            ['id' => 'a1b2c3d4-0001-0001-0001-000000000001', 'type_name' => 'Makanan Pokok'],
            ['id' => 'a1b2c3d4-0002-0002-0002-000000000002', 'type_name' => 'Minuman'],
            ['id' => 'a1b2c3d4-0003-0003-0003-000000000003', 'type_name' => 'Snack'],
            ['id' => 'a1b2c3d4-0004-0004-0004-000000000004', 'type_name' => 'Sembako'],
            ['id' => 'a1b2c3d4-0005-0005-0005-000000000005', 'type_name' => 'Elektronik'],
            ['id' => 'a1b2c3d4-0006-0006-0006-000000000006', 'type_name' => 'Fashion'],
            ['id' => 'a1b2c3d4-0007-0007-0007-000000000007', 'type_name' => 'Kesehatan'],
            ['id' => 'a1b2c3d4-0008-0008-0008-000000000008', 'type_name' => 'Otomotif'],
            ['id' => 'a1b2c3d4-0009-0009-0009-000000000009', 'type_name' => 'Rumah Tangga'],
            ['id' => 'a1b2c3d4-0010-0010-0010-000000000010', 'type_name' => 'ATK'],
        ];

        foreach ($types as $type) {
            ProductType::create([
                'id' => $type['id'],
                'type_name' => $type['type_name'],
                'created_at' => now(),
            ]);
        }

        // =========================
        // PRODUCTS (20 DATA)
        // =========================

        $products = [
            // Makanan Pokok
            ['type_id' => $types[0]['id'], 'name' => 'Beras 5kg', 'price' => 85000, 'stock' => 10],
            ['type_id' => $types[0]['id'], 'name' => 'Indomie Goreng', 'price' => 3500, 'stock' => 100],

            // Minuman
            ['type_id' => $types[1]['id'], 'name' => 'Teh Botol', 'price' => 5000, 'stock' => 50],
            ['type_id' => $types[1]['id'], 'name' => 'Aqua 600ml', 'price' => 3000, 'stock' => 200],

            // Snack
            ['type_id' => $types[2]['id'], 'name' => 'Chitato', 'price' => 12000, 'stock' => 40],
            ['type_id' => $types[2]['id'], 'name' => 'Taro', 'price' => 10000, 'stock' => 60],

            // Sembako
            ['type_id' => $types[3]['id'], 'name' => 'Gula 1kg', 'price' => 14000, 'stock' => 30],
            ['type_id' => $types[3]['id'], 'name' => 'Minyak 1L', 'price' => 17000, 'stock' => 25],

            // Elektronik
            ['type_id' => $types[4]['id'], 'name' => 'Mouse Logitech', 'price' => 120000, 'stock' => 15],
            ['type_id' => $types[4]['id'], 'name' => 'Keyboard Mechanical', 'price' => 350000, 'stock' => 8],

            // Fashion
            ['type_id' => $types[5]['id'], 'name' => 'Kaos Polos', 'price' => 50000, 'stock' => 20],
            ['type_id' => $types[5]['id'], 'name' => 'Hoodie', 'price' => 150000, 'stock' => 12],

            // Kesehatan
            ['type_id' => $types[6]['id'], 'name' => 'Masker', 'price' => 20000, 'stock' => 100],
            ['type_id' => $types[6]['id'], 'name' => 'Hand Sanitizer', 'price' => 25000, 'stock' => 70],

            // Otomotif
            ['type_id' => $types[7]['id'], 'name' => 'Oli Motor', 'price' => 45000, 'stock' => 30],
            ['type_id' => $types[7]['id'], 'name' => 'Busi Motor', 'price' => 15000, 'stock' => 50],

            // Rumah Tangga
            ['type_id' => $types[8]['id'], 'name' => 'Sabun Cuci', 'price' => 8000, 'stock' => 90],
            ['type_id' => $types[8]['id'], 'name' => 'Sapu', 'price' => 25000, 'stock' => 25],

            // ATK
            ['type_id' => $types[9]['id'], 'name' => 'Pulpen', 'price' => 3000, 'stock' => 300],
            ['type_id' => $types[9]['id'], 'name' => 'Buku Tulis', 'price' => 5000, 'stock' => 150],
        ];

        foreach ($products as $p) {
            Product::create([
                'type_id' => $p['type_id'],
                'name' => $p['name'],
                'desc' => $p['name'] . ' berkualitas tinggi',
                'price' => $p['price'],
                'stock' => $p['stock'],
                'status' => 'active',
                'created_at' => now(),
            ]);
        }
    }
}