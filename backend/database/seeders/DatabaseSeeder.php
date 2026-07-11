<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Compte admin de TEST uniquement — jamais de vraies données ici.
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@demo.local',
            'password' => Hash::make('DemoPassword123!'),
            'role' => 'admin',
            'whatsapp_phone' => '+212634713170',
            'is_active' => true,
        ]);

        // Catégories de test
        $categories = [
            ['name' => 'Huiles essentielles', 'slug' => 'huiles-essentielles', 'description' => 'Huiles essentielles naturelles et pures.'],
            ['name' => 'Savons artisanaux', 'slug' => 'savons-artisanaux', 'description' => 'Savons faits main, ingrédients naturels.'],
            ['name' => 'Parfums', 'slug' => 'parfums', 'description' => 'Parfums authentiques inspirés des traditions locales.'],
            ['name' => 'Cosmétiques naturels', 'slug' => 'cosmetiques-naturels', 'description' => 'Soins du visage et du corps 100% naturels.'],
        ];

        foreach ($categories as $index => $cat) {
            $category = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['description'],
                'is_active' => true,
                'sort_order' => $index,
            ]);

            // 2 produits de test par catégorie
            for ($i = 1; $i <= 2; $i++) {
                Product::create([
                    'name' => $cat['name'] . ' - Produit Test ' . $i,
                    'slug' => $cat['slug'] . '-produit-test-' . $i,
                    'description' => 'Produit de démonstration pour la catégorie ' . $cat['name'] . '.',
                    'base_price' => rand(50, 300),
                    'category_id' => $category->id,
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}