<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create sample categories with specific names
        $categories = [
            ProductCategory::create([
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'is_active' => true,
            ]),
            ProductCategory::create([
                'name' => 'Clothing',
                'description' => 'Fashion and apparel',
                'is_active' => true,
            ]),
            ProductCategory::create([
                'name' => 'Books',
                'description' => 'Books and educational materials',
                'is_active' => true,
            ]),
            ProductCategory::create([
                'name' => 'Home & Garden',
                'description' => 'Home improvement and gardening',
                'is_active' => true,
            ]),
            ProductCategory::create([
                'name' => 'Sports',
                'description' => 'Sports equipment and accessories',
                'is_active' => true,
            ]),
        ];

        // Create sample products with proper data
        $products = Product::factory()->count(50)->create();

        // Attach random categories to products (many-to-many)
        $products->each(function ($product) use ($categories) {
            // Each product gets 1-3 random categories
            $randomCategories = collect($categories)->random(rand(1, 3))->pluck('id');
            $product->categories()->attach($randomCategories);
        });

        // Create regular users
        User::factory()->count(10)->create();
    }
}