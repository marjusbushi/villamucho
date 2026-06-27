<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenitiesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_add_and_delete_a_master_amenity(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('settings.amenities.store'), ['name' => 'Ballkon'])
            ->assertRedirect();
        $this->assertDatabaseHas('amenities', ['name' => 'Ballkon']);

        $amenity = Amenity::first();
        $this->actingAs($admin)
            ->delete(route('settings.amenities.destroy', $amenity->id))
            ->assertRedirect();
        $this->assertDatabaseMissing('amenities', ['id' => $amenity->id]);
    }

    public function test_duplicate_amenity_name_is_rejected(): void
    {
        $admin = $this->admin();
        Amenity::create(['name' => 'WiFi', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->post(route('settings.amenities.store'), ['name' => 'WiFi'])
            ->assertSessionHasErrors('name');
    }
}
