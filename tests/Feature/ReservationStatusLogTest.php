<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\ReservationStatusLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ChannexBookingImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task #190 (Copa 1b): every reservation status transition is recorded in
 * reservation_status_logs — including OTA cancellations from the Channex
 * importer, whose old mass ->update() bypassed model events.
 */
class ReservationStatusLogTest extends TestCase
{
    use RefreshDatabase;

    private function stay(string $status = 'pending', array $extra = []): Reservation
    {
        $type = RoomType::firstOrCreate(['name' => 'Std'], ['base_price' => 100, 'max_occupancy' => 3, 'amenities' => []]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => fake()->unique()->numerify('###'), 'floor' => 1, 'status' => 'available']);

        return Reservation::create(array_merge([
            'room_id' => $room->id,
            'guest_id' => Guest::create(['first_name' => 'T', 'last_name' => 'G'])->id,
            'created_by' => User::factory()->create()->id,
            'check_in_date' => now()->addDays(2)->toDateString(),
            'check_out_date' => now()->addDays(4)->toDateString(),
            'status' => $status,
            'total_amount' => 200,
            'adults' => 2,
            'channel' => 'direct',
        ], $extra));
    }

    public function test_create_confirm_cancel_produce_three_log_rows(): void
    {
        $res = $this->stay('pending');

        $res->status = 'confirmed';
        $res->save();
        $res->status = 'cancelled';
        $res->save();

        $logs = ReservationStatusLog::where('reservation_id', $res->id)->orderBy('id')->get();
        $this->assertCount(3, $logs);
        $this->assertNull($logs[0]->from_status);
        $this->assertSame('pending', $logs[0]->to_status);
        $this->assertSame(['pending', 'confirmed'], [$logs[1]->from_status, $logs[1]->to_status]);
        $this->assertSame(['confirmed', 'cancelled'], [$logs[2]->from_status, $logs[2]->to_status]);
        $this->assertNotNull($logs[2]->created_at, 'cancellation time is recorded');
    }

    public function test_actor_is_recorded_when_authenticated_and_null_for_system(): void
    {
        $res = $this->stay('pending'); // created without auth → system
        $this->assertNull(ReservationStatusLog::where('reservation_id', $res->id)->first()->changed_by);

        $staff = User::factory()->create();
        $this->actingAs($staff);
        $res->status = 'confirmed';
        $res->save();

        $this->assertSame($staff->id, ReservationStatusLog::where('reservation_id', $res->id)
            ->where('to_status', 'confirmed')->first()->changed_by);
    }

    public function test_non_status_updates_do_not_log(): void
    {
        $res = $this->stay('confirmed');
        ReservationStatusLog::query()->delete();

        $res->notes = 'ndryshim shënimi';
        $res->save();

        $this->assertSame(0, ReservationStatusLog::count());
    }

    public function test_channex_importer_cancellation_is_captured(): void
    {
        $res = $this->stay('confirmed', ['channel' => 'booking.com', 'channel_ref' => 'BK-99']);
        ReservationStatusLog::query()->delete();

        $summary = app(ChannexBookingImporter::class)->importRevision([
            'id' => 'rev-1',
            'attributes' => [
                'status' => 'cancelled',
                'ota_reservation_code' => 'BK-99',
                'ota_name' => 'Booking.com',
            ],
        ]);

        $this->assertSame(1, $summary['cancelled']);
        $this->assertSame('cancelled', $res->fresh()->status);

        $log = ReservationStatusLog::where('reservation_id', $res->id)->first();
        $this->assertNotNull($log, 'importer cancel must fire the status log (mass-update bypass fixed)');
        $this->assertSame('confirmed', $log->from_status);
        $this->assertSame('cancelled', $log->to_status);
        $this->assertNull($log->changed_by, 'webhook/system cancel has no acting user');
    }
}
