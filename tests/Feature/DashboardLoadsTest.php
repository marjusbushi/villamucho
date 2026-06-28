<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardLoadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_new_cockpit_props(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = RoomType::create(['name' => 'Std', 'base_price' => 80, 'max_occupancy' => 3, 'amenities' => []]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'status' => 'occupied']);
        $guest = Guest::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.local', 'phone' => '1']);
        Reservation::create([
            'room_id' => $room->id, 'guest_id' => $guest->id, 'created_by' => $admin->id,
            'check_in_date' => today()->toDateString(), 'check_out_date' => today()->addDays(2)->toDateString(),
            'status' => 'checked_in', 'total_amount' => 160, 'adults' => 2, 'channel' => 'booking.com',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('Dashboard')
                ->has('stats.occupancy')->has('stats.revenue_today')->has('stats.outstanding')
                ->has('charts.revenue14')->has('charts.occupancy14')->has('charts.channelMix')
                ->has('roomStatusCounts')->has('alerts')
            );
    }
}
