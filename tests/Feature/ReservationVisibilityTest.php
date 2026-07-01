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

class ReservationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_reservation_shows_on_calendar_and_list(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $type = RoomType::create(['name' => 'Std', 'base_price' => 100, 'max_occupancy' => 3, 'amenities' => []]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => '301', 'floor' => 3, 'status' => 'available']);
        $guest = Guest::create(['first_name' => 'Besim', 'last_name' => 'Wp', 'phone' => '+38349966102']);

        // Dates inside the calendar's default 14-day window (startOfWeek .. +13).
        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        $this->actingAs($admin)->post(route('reservations.store-multi'), [
            'guest_id' => $guest->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'channel' => 'direct',
            'rooms' => [
                ['room_id' => $room->id, 'adults' => 2, 'children' => 0, 'total_amount' => 300],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        // It must actually persist.
        $this->assertCount(1, Reservation::all());
        $this->assertEquals($room->id, Reservation::first()->room_id);

        // It must appear on the calendar (within the default window).
        $this->actingAs($admin)->get(route('reservations.calendar'))
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('Reservations/Calendar')
                ->has('reservations', 1));

        // And on the list.
        $this->actingAs($admin)->get(route('reservations.index'))
            ->assertInertia(fn (AssertableInertia $p) => $p
                ->component('Reservations/Index')
                ->has('reservations.data', 1));
    }
}
