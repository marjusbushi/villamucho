<?php

namespace Tests\Feature;

use App\Models\FolioItem;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RoomRackTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: RoomType} */
    private function hotel(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $roomType = RoomType::create([
            'name' => 'Standard',
            'base_price' => 100,
            'max_occupancy' => 3,
            'amenities' => [],
        ]);

        return [$admin, $roomType];
    }

    private function room(RoomType $roomType, string $number, int $floor = 1, string $status = 'available'): Room
    {
        return Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => $number,
            'floor' => $floor,
            'status' => $status,
        ]);
    }

    private function guest(string $firstName, string $lastName): Guest
    {
        return Guest::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName).'@example.test',
            'phone' => '+355690000000',
        ]);
    }

    private function reservation(
        Room $room,
        Guest $guest,
        User $admin,
        string $status,
        string $checkIn,
        string $checkOut,
        float $total = 100,
    ): Reservation {
        return Reservation::create([
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'created_by' => $admin->id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'status' => $status,
            'total_amount' => $total,
            'adults' => 2,
            'children' => 1,
            'channel' => 'direct',
        ]);
    }

    /** @return array<string, mixed> */
    private function props(TestResponse $response): array
    {
        $page = json_decode(json_encode($response->viewData('page'), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $page['props'];
    }

    public function test_room_rack_returns_every_room_with_operational_payload_and_constant_relation_queries(): void
    {
        $this->travelTo('2026-07-10 10:00:00');
        [$admin, $roomType] = $this->hotel();

        $activeRoom = $this->room($roomType, '001', 1, 'occupied');
        $arrivalRoom = $this->room($roomType, '002');
        $cleaningRoom = $this->room($roomType, '003', 1, 'cleaning');
        $maintenanceRoom = $this->room($roomType, '004', 1, 'maintenance');
        $nextRoom = $this->room($roomType, '005');
        $pendingRoom = $this->room($roomType, '006');
        for ($number = 7; $number <= 25; $number++) {
            $this->room($roomType, str_pad((string) $number, 3, '0', STR_PAD_LEFT));
        }

        $active = $this->reservation(
            $activeRoom,
            $this->guest('Ana', 'Hoxha'),
            $admin,
            'checked_in',
            '2026-07-09',
            '2026-07-10',
            200,
        );
        FolioItem::create([
            'reservation_id' => $active->id,
            'description' => 'Airport transfer',
            'amount' => 30,
            'type' => 'extra',
            'charge_date' => '2026-07-09',
        ]);
        FolioItem::create([
            'reservation_id' => $active->id,
            'description' => 'Discount',
            'amount' => 10,
            'type' => 'discount',
            'charge_date' => '2026-07-09',
        ]);
        FolioItem::create([
            'reservation_id' => $active->id,
            'description' => 'Room duplicate must be ignored',
            'amount' => 200,
            'type' => 'room',
            'charge_date' => '2026-07-09',
        ]);
        Payment::create([
            'reservation_id' => $active->id,
            'amount' => 50,
            'method' => 'cash',
            'created_by' => $admin->id,
            'is_voided' => false,
        ]);
        Payment::create([
            'reservation_id' => $active->id,
            'amount' => 99,
            'method' => 'card',
            'created_by' => $admin->id,
            'is_voided' => true,
        ]);

        $arrival = $this->reservation(
            $arrivalRoom,
            $this->guest('Bora', 'Deda'),
            $admin,
            'confirmed',
            '2026-07-10',
            '2026-07-12',
        );
        $cleaningArrival = $this->reservation(
            $cleaningRoom,
            $this->guest('Dren', 'Kola'),
            $admin,
            'confirmed',
            '2026-07-10',
            '2026-07-11',
        );
        $maintenanceArrival = $this->reservation(
            $maintenanceRoom,
            $this->guest('Flora', 'Nika'),
            $admin,
            'confirmed',
            '2026-07-10',
            '2026-07-12',
        );
        $pendingArrival = $this->reservation(
            $pendingRoom,
            $this->guest('Genti', 'Pasha'),
            $admin,
            'pending',
            '2026-07-10',
            '2026-07-11',
        );
        $next = $this->reservation(
            $nextRoom,
            $this->guest('Era', 'Mema'),
            $admin,
            'confirmed',
            '2026-07-15',
            '2026-07-18',
        );
        Setting::set('financial.channel_fees', ['booking.com' => 15], 'json');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($admin)->get(route('rooms.index'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->map(fn (string $query) => strtolower($query));
        DB::disableQueryLog();

        $props = $this->props($response);
        $this->assertCount(25, $props['rooms']['data']);
        $this->assertSame(25, $props['rooms']['total']);
        $this->assertNotNull(collect($props['rooms']['data'])->firstWhere('room_number', '025'));

        $activeRow = collect($props['rooms']['data'])->firstWhere('id', $activeRoom->id);
        $this->assertSame('departing_today', data_get($activeRow, 'operational.state'));
        $this->assertSame($active->id, data_get($activeRow, 'operational.active_stay.id'));
        $this->assertSame($active->id, data_get($activeRow, 'operational.departure_today.id'));
        $this->assertSame('Ana Hoxha', data_get($activeRow, 'operational.guest.name'));
        $this->assertEqualsWithDelta(170, data_get($activeRow, 'operational.outstanding_balance'), 0.001);
        $this->assertEqualsWithDelta(170, data_get($activeRow, 'operational.outstanding'), 0.001);
        $this->assertSame(3, data_get($activeRow, 'room_type.max_occupancy'));
        $this->assertSame('check_out', data_get($activeRow, 'operational.primary_action.key'));
        $this->assertTrue(data_get($activeRow, 'operational.primary_action.requires_settle_method'));
        $this->assertFalse(data_get($activeRow, 'operational.primary_action.safe_inline'));

        $arrivalRow = collect($props['rooms']['data'])->firstWhere('id', $arrivalRoom->id);
        $this->assertSame($arrival->id, data_get($arrivalRow, 'operational.arrival_today.id'));
        $this->assertSame('arrival_today', data_get($arrivalRow, 'operational.state'));
        $this->assertSame('check_in', data_get($arrivalRow, 'operational.primary_action.key'));
        $this->assertTrue(data_get($arrivalRow, 'operational.primary_action.safe_inline'));

        $cleaningRow = collect($props['rooms']['data'])->firstWhere('id', $cleaningRoom->id);
        $this->assertSame($cleaningArrival->id, data_get($cleaningRow, 'operational.arrival_today.id'));
        $this->assertSame('cleaning_for_arrival', data_get($cleaningRow, 'operational.state'));
        $this->assertSame('housekeeping', data_get($cleaningRow, 'operational.primary_action.key'));

        $maintenanceRow = collect($props['rooms']['data'])->firstWhere('id', $maintenanceRoom->id);
        $this->assertSame($maintenanceArrival->id, data_get($maintenanceRow, 'operational.arrival_today.id'));
        $this->assertSame('maintenance', data_get($maintenanceRow, 'operational.state'));
        $this->assertSame('maintenance', data_get($maintenanceRow, 'operational.primary_action.key'));

        $pendingRow = collect($props['rooms']['data'])->firstWhere('id', $pendingRoom->id);
        $this->assertSame($pendingArrival->id, data_get($pendingRow, 'operational.arrival_today.id'));
        $this->assertSame('arrival_pending', data_get($pendingRow, 'operational.state'));
        $this->assertSame('view_reservation', data_get($pendingRow, 'operational.primary_action.key'));
        $this->assertFalse(data_get($pendingRow, 'operational.primary_action.safe_inline'));

        $nextRow = collect($props['rooms']['data'])->firstWhere('id', $nextRoom->id);
        $this->assertSame($next->id, data_get($nextRow, 'operational.next_reservation.id'));
        $this->assertSame('vacant_reserved', data_get($nextRow, 'operational.state'));

        $this->assertSame([
            'total' => 25,
            'arrivals_today' => 4,
            'departures_today' => 1,
            'occupied' => 1,
            'cleaning' => 1,
            'maintenance' => 1,
        ], $props['stats']);
        $this->assertSame(['booking.com' => 15], $props['channelFees']);
        $this->assertCount(6, $props['guests']);
        $this->assertSame(
            ['id', 'first_name', 'last_name', 'email', 'phone'],
            array_keys($props['guests'][0]),
        );

        foreach (['guests', 'folio_items', 'payments'] as $table) {
            $count = $queries->filter(fn (string $query) => preg_match('/from [`"]?'.$table.'[`"]?/', $query) === 1)->count();
            $this->assertSame(1, $count, "Expected exactly one eager-load query for {$table}.");
        }
    }

    public function test_room_rack_searches_room_number_and_operational_guest_name(): void
    {
        $this->travelTo('2026-07-10 10:00:00');
        [$admin, $roomType] = $this->hotel();
        $guestRoom = $this->room($roomType, '101');
        $numberRoom = $this->room($roomType, 'SPECIAL-202');
        $this->room($roomType, '303');

        $this->reservation(
            $guestRoom,
            $this->guest('Elira', 'Kodra'),
            $admin,
            'confirmed',
            '2026-07-15',
            '2026-07-17',
        );

        $guestProps = $this->props($this->actingAs($admin)->get(route('rooms.index', ['search' => 'kOdRa'])));
        $this->assertSame('kOdRa', $guestProps['filters']['search']);
        $this->assertSame([$guestRoom->id], collect($guestProps['rooms']['data'])->pluck('id')->all());

        $numberProps = $this->props($this->actingAs($admin)->get(route('rooms.index', ['search' => 'special'])));
        $this->assertSame([$numberRoom->id], collect($numberProps['rooms']['data'])->pluck('id')->all());
    }

    public function test_room_rack_preserves_status_floor_and_room_type_filters(): void
    {
        [$admin, $standard] = $this->hotel();
        $suite = RoomType::create([
            'name' => 'Suite',
            'base_price' => 200,
            'max_occupancy' => 4,
            'amenities' => [],
        ]);

        $matching = $this->room($standard, '101', 1, 'available');
        $this->room($standard, '102', 2, 'available');
        $this->room($standard, '103', 1, 'cleaning');
        $this->room($suite, '201', 1, 'available');

        $props = $this->props($this->actingAs($admin)->get(route('rooms.index', [
            'status' => 'available',
            'floor' => 1,
            'room_type_id' => $standard->id,
        ])));

        $this->assertSame([$matching->id], collect($props['rooms']['data'])->pluck('id')->all());
        $this->assertSame('available', $props['filters']['status']);
        $this->assertSame('1', (string) $props['filters']['floor']);
        $this->assertSame((string) $standard->id, (string) $props['filters']['room_type_id']);
    }

    public function test_housekeeping_sees_room_states_but_no_reservation_pii_financials_or_actions(): void
    {
        $this->travelTo('2026-07-10 10:00:00');
        [$admin, $roomType] = $this->hotel();
        $housekeeper = User::factory()->create();
        $housekeeper->assignRole('housekeeping');

        $occupiedRoom = $this->room($roomType, '901', 9, 'occupied');
        $cleaningRoom = $this->room($roomType, '902', 9, 'cleaning');
        $maintenanceRoom = $this->room($roomType, '903', 9, 'maintenance');
        $guest = $this->guest('Private', 'Guest');
        $reservation = $this->reservation(
            $occupiedRoom,
            $guest,
            $admin,
            'checked_in',
            '2026-07-10',
            '2026-07-11',
            250,
        );
        FolioItem::create([
            'reservation_id' => $reservation->id,
            'description' => 'Private charge',
            'amount' => 35,
            'type' => 'extra',
            'charge_date' => '2026-07-10',
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 50,
            'method' => 'cash',
            'created_by' => $admin->id,
        ]);
        Setting::set('financial.channel_fees', ['booking.com' => 15], 'json');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($housekeeper)->get(route('rooms.index'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->map(fn (string $query) => strtolower($query));
        DB::disableQueryLog();

        $props = $this->props($response);
        $this->assertArrayNotHasKey('guests', $props);
        $this->assertArrayNotHasKey('channelFees', $props);
        $this->assertSame([
            'total' => 3,
            'occupied' => 1,
            'cleaning' => 1,
            'maintenance' => 1,
        ], $props['stats']);

        $occupied = collect($props['rooms']['data'])->firstWhere('id', $occupiedRoom->id);
        $this->assertSame('occupied', data_get($occupied, 'operational.state'));
        $this->assertSame('occupied', data_get($occupied, 'operational.occupancy_status'));
        $this->assertSame(
            ['state', 'occupancy_status', 'housekeeping_status', 'service_status', 'out_of_order'],
            array_keys($occupied['operational']),
        );
        foreach ([
            'primary_action', 'reservation_id', 'active_stay', 'arrival_today',
            'departure_today', 'next_reservation', 'guest', 'outstanding',
            'outstanding_balance',
        ] as $sensitiveKey) {
            $this->assertArrayNotHasKey($sensitiveKey, $occupied['operational']);
        }

        $cleaning = collect($props['rooms']['data'])->firstWhere('id', $cleaningRoom->id);
        $maintenance = collect($props['rooms']['data'])->firstWhere('id', $maintenanceRoom->id);
        $this->assertSame('cleaning', data_get($cleaning, 'operational.state'));
        $this->assertSame('dirty', data_get($cleaning, 'operational.housekeeping_status'));
        $this->assertSame('maintenance', data_get($maintenance, 'operational.state'));
        $this->assertTrue(data_get($maintenance, 'operational.out_of_order'));

        $serializedRooms = json_encode($props['rooms']['data'], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($guest->email, $serializedRooms);
        $this->assertStringNotContainsString($guest->phone, $serializedRooms);
        $this->assertStringNotContainsString($guest->full_name, $serializedRooms);

        foreach (['guests', 'folio_items', 'payments'] as $table) {
            $count = $queries->filter(fn (string $query) => preg_match('/from [`"]?'.$table.'[`"]?/', $query) === 1)->count();
            $this->assertSame(0, $count, "Unauthorized rack must not query {$table}.");
        }
    }

    public function test_reservation_creator_without_guest_access_gets_no_guest_pii_or_rack_create_action(): void
    {
        $this->travelTo('2026-07-10 10:00:00');
        [$admin, $roomType] = $this->hotel();
        $limitedCreator = User::factory()->create();
        $limitedCreator->givePermissionTo([
            'view_rooms',
            'view_reservations',
            'create_reservations',
        ]);

        $reservedRoom = $this->room($roomType, '801');
        $vacantRoom = $this->room($roomType, '802');
        $guest = $this->guest('Hidden', 'Contact');
        $this->reservation(
            $reservedRoom,
            $guest,
            $admin,
            'confirmed',
            '2026-07-15',
            '2026-07-17',
        );
        Setting::set('financial.channel_fees', ['booking.com' => 15], 'json');

        $props = $this->props($this->actingAs($limitedCreator)->get(route('rooms.index'))->assertOk());

        $this->assertArrayNotHasKey('guests', $props);
        $this->assertArrayNotHasKey('channelFees', $props);

        $reserved = collect($props['rooms']['data'])->firstWhere('id', $reservedRoom->id);
        $guestPayload = data_get($reserved, 'operational.next_reservation.guest');
        $this->assertSame($guest->full_name, $guestPayload['name']);
        $this->assertArrayNotHasKey('email', $guestPayload);
        $this->assertArrayNotHasKey('phone', $guestPayload);

        $vacant = collect($props['rooms']['data'])->firstWhere('id', $vacantRoom->id);
        $this->assertNull(data_get($vacant, 'operational.primary_action'));
    }
}
