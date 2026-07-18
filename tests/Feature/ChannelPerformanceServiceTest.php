<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Reporting\ChannelPerformanceService;
use App\Services\Reporting\ReportingPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allocates_channel_revenue_by_stay_date_and_compares_direct_with_ota(): void
    {
        $user = User::factory()->create();
        $guest = Guest::create(['first_name' => 'Test', 'last_name' => 'Guest']);
        $type = RoomType::create(['name' => 'Standard', 'base_price' => 100, 'max_occupancy' => 2, 'amenities' => []]);
        $directRoom = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'status' => 'available']);
        $otaRoom = Room::create(['room_type_id' => $type->id, 'room_number' => '102', 'floor' => 1, 'status' => 'available']);

        Reservation::create([
            'room_id' => $directRoom->id,
            'guest_id' => $guest->id,
            'created_by' => $user->id,
            'check_in_date' => '2026-06-30',
            'check_out_date' => '2026-07-03',
            'status' => 'confirmed',
            'total_amount' => 300,
            'commission_amount' => 0,
            'adults' => 1,
            'channel' => 'direct',
        ]);
        Reservation::create([
            'room_id' => $otaRoom->id,
            'guest_id' => $guest->id,
            'created_by' => $user->id,
            'check_in_date' => '2026-07-01',
            'check_out_date' => '2026-07-03',
            'status' => 'confirmed',
            'total_amount' => 240,
            'commission_amount' => 24,
            'adults' => 1,
            'channel' => 'booking.com',
        ]);

        $analytics = app(ChannelPerformanceService::class)
            ->withComparisons(new ReportingPeriod('2026-07-01', '2026-07-02'));
        $current = $analytics['current'];
        $direct = collect($current['rows'])->firstWhere('channel', 'direct');
        $ota = collect($current['rows'])->firstWhere('channel', 'booking.com');

        $this->assertSame(440.0, $current['totals']['gross_revenue']);
        $this->assertSame(24.0, $current['totals']['commission']);
        $this->assertSame(416.0, $current['totals']['net_revenue']);
        $this->assertSame(4, $current['totals']['nights']);
        $this->assertSame(45.5, $current['totals']['direct_share']);
        $this->assertSame(104.0, $current['totals']['net_adr']);
        $this->assertSame(200.0, $direct['gross_revenue']);
        $this->assertSame(240.0, $ota['gross_revenue']);
        $this->assertSame(24.0, $ota['commission']);
        $this->assertSame(108.0, $current['daily']['2026-07-01']['ota_net']);
        $this->assertSame(-54.5, $analytics['changes']['direct_share']);
    }
}
