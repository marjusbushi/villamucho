<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $tenant = Tenant::query()->sole();
        app(TenantContext::class)->set($tenant);

        $this->staff = User::factory()->create(['current_tenant_id' => $tenant->id]);
        $this->staff->tenants()->syncWithoutDetaching([
            $tenant->id => ['is_owner' => true, 'is_active' => true],
        ]);
        $this->staff->assignRole('admin');

        $type = RoomType::create(['name' => 'Standard', 'base_price' => 360, 'max_occupancy' => 3, 'amenities' => []]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => '301', 'floor' => 3, 'status' => 'available']);
        $guest = Guest::create(['first_name' => 'Sarah', 'last_name' => 'Johnson']);

        $this->reservation = Reservation::create([
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'created_by' => $this->staff->id,
            'check_in_date' => now()->addDay()->toDateString(),
            'check_out_date' => now()->addDays(4)->toDateString(),
            'status' => 'pending',
            'total_amount' => 360,
            'adults' => 2,
            'channel' => 'direct',
        ]);
    }

    public function test_payment_returns_json_and_is_recorded(): void
    {
        $this->actingAs($this->staff)
            ->postJson(route('reservations.payment', $this->reservation), [
                'amount' => 360,
                'method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Pagesa u regjistrua.');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $this->reservation->id,
            'amount' => 360,
            'method' => 'cash',
            'created_by' => $this->staff->id,
        ]);
    }

    public function test_payment_cannot_exceed_the_live_outstanding_balance(): void
    {
        $this->actingAs($this->staff)
            ->postJson(route('reservations.payment', $this->reservation), [
                'amount' => 360.01,
                'method' => 'card',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_reservation_cannot_receive_a_payment(): void
    {
        $this->reservation->update(['status' => 'cancelled']);

        $this->actingAs($this->staff)
            ->postJson(route('reservations.payment', $this->reservation), [
                'amount' => 10,
                'method' => 'cash',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }
}
