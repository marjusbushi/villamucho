<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $drinks = MenuCategory::firstOrCreate(['name' => 'Pije'], ['sort_order' => 1]);
        $food = MenuCategory::firstOrCreate(['name' => 'Ushqim'], ['sort_order' => 2]);
        $dessert = MenuCategory::firstOrCreate(['name' => 'Dessert'], ['sort_order' => 3]);

        $items = [
            // Pije
            ['menu_category_id' => $drinks->id, 'name' => 'Espresso', 'price' => 1.50],
            ['menu_category_id' => $drinks->id, 'name' => 'Cappuccino', 'price' => 2.50],
            ['menu_category_id' => $drinks->id, 'name' => 'Caj Mali', 'price' => 2.00],
            ['menu_category_id' => $drinks->id, 'name' => 'Leng Portokalli', 'price' => 3.00],
            ['menu_category_id' => $drinks->id, 'name' => 'Bire Korce', 'price' => 3.50],
            ['menu_category_id' => $drinks->id, 'name' => 'Vere e Kuqe (gote)', 'price' => 5.00],
            ['menu_category_id' => $drinks->id, 'name' => 'Uje Mineral', 'price' => 1.00],
            // Ushqim
            ['menu_category_id' => $food->id, 'name' => 'Sandvic Klubi', 'price' => 6.00],
            ['menu_category_id' => $food->id, 'name' => 'Salate Cezar', 'price' => 7.00],
            ['menu_category_id' => $food->id, 'name' => 'Burger Classic', 'price' => 8.50],
            ['menu_category_id' => $food->id, 'name' => 'Pasta Carbonara', 'price' => 9.00],
            ['menu_category_id' => $food->id, 'name' => 'Pizza Margherita', 'price' => 8.00],
            // Dessert
            ['menu_category_id' => $dessert->id, 'name' => 'Tiramisu', 'price' => 5.00],
            ['menu_category_id' => $dessert->id, 'name' => 'Akullore (3 topa)', 'price' => 4.00],
            ['menu_category_id' => $dessert->id, 'name' => 'Panna Cotta', 'price' => 4.50],
        ];

        foreach ($items as $item) {
            MenuItem::firstOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
