<?php

namespace Tests\Feature;

use App\Models\Floor;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloorsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_create_and_update_a_floor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('settings.floors.store'), ['number' => 1, 'name' => 'Kati 1'])
            ->assertRedirect();
        $this->assertDatabaseHas('floors', ['number' => 1, 'name' => 'Kati 1']);

        $floor = Floor::first();
        $this->actingAs($admin)
            ->put(route('settings.floors.update', $floor->id), ['number' => 1, 'name' => 'Kati Pare'])
            ->assertRedirect();
        $this->assertEquals('Kati Pare', $floor->fresh()->name);
    }

    public function test_floor_number_must_be_unique(): void
    {
        $admin = $this->admin();
        Floor::create(['number' => 1, 'name' => 'Kati 1']);

        $this->actingAs($admin)
            ->post(route('settings.floors.store'), ['number' => 1, 'name' => 'Duplicate'])
            ->assertSessionHasErrors('number');
    }

    public function test_floor_with_rooms_cannot_be_deleted_but_empty_one_can(): void
    {
        $admin = $this->admin();
        $type = RoomType::create(['name' => 'Std', 'base_price' => 50, 'max_occupancy' => 2, 'amenities' => []]);

        $occupied = Floor::create(['number' => 2, 'name' => 'Kati 2']);
        Room::create(['room_type_id' => $type->id, 'room_number' => '201', 'floor' => 2, 'status' => 'available']);

        $this->actingAs($admin)
            ->delete(route('settings.floors.destroy', $occupied->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('floors', ['id' => $occupied->id]);

        $empty = Floor::create(['number' => 3, 'name' => 'Kati 3']);
        $this->actingAs($admin)
            ->delete(route('settings.floors.destroy', $empty->id))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('floors', ['id' => $empty->id]);
    }
}
