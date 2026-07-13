<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PosWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_menu_exposes_recent_completed_sales_for_frequent_items(): void
    {
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = MenuCategory::create(['name' => 'Pije', 'sort_order' => 1]);
        $espresso = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Espresso',
            'price' => 1.5,
            'is_available' => true,
        ]);
        $water = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Uje',
            'price' => 1,
            'is_available' => true,
        ]);

        $completed = PosOrder::create([
            'status' => 'completed',
            'payment_method' => 'cash',
            'total_amount' => 6,
            'created_by' => $admin->id,
        ]);
        PosOrderItem::create([
            'pos_order_id' => $completed->id,
            'menu_item_id' => $espresso->id,
            'quantity' => 4,
            'unit_price' => 1.5,
            'total_price' => 6,
        ]);

        $open = PosOrder::create([
            'status' => 'open',
            'total_amount' => 20,
            'created_by' => $admin->id,
        ]);
        PosOrderItem::create([
            'pos_order_id' => $open->id,
            'menu_item_id' => $water->id,
            'quantity' => 20,
            'unit_price' => 1,
            'total_price' => 20,
        ]);

        $this->actingAs($admin)->get(route('pos.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Pos/Index')
                ->where('menu.0.items.0.name', 'Espresso')
                ->where('menu.0.items.0.sales_count', 4)
                ->where('menu.0.items.1.name', 'Uje')
                ->where('menu.0.items.1.sales_count', 0));
    }
}
