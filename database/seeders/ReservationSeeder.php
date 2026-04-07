<?php

namespace Database\Seeders;

use App\Models\FolioItem;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@chanelmanager.com')->first();
        $guests = Guest::all();
        $rooms = Room::with('roomType')->get();

        $reservations = [
            // Active — checked in
            [
                'room' => '103', 'guest_idx' => 0, 'status' => 'checked_in',
                'check_in' => now()->subDays(2)->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
            ],
            [
                'room' => '202', 'guest_idx' => 1, 'status' => 'checked_in',
                'check_in' => now()->subDay()->toDateString(),
                'check_out' => now()->addDays(4)->toDateString(),
            ],
            [
                'room' => '204', 'guest_idx' => 2, 'status' => 'checked_in',
                'check_in' => now()->subDays(3)->toDateString(),
                'check_out' => now()->addDays(1)->toDateString(),
            ],
            // Confirmed — arriving soon
            [
                'room' => '101', 'guest_idx' => 3, 'status' => 'confirmed',
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
            ],
            [
                'room' => '201', 'guest_idx' => 4, 'status' => 'confirmed',
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(6)->toDateString(),
            ],
            // Pending
            [
                'room' => '301', 'guest_idx' => 5, 'status' => 'pending',
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(8)->toDateString(),
            ],
            // Past — checked out
            [
                'room' => '104', 'guest_idx' => 6, 'status' => 'checked_out',
                'check_in' => now()->subDays(7)->toDateString(),
                'check_out' => now()->subDays(3)->toDateString(),
            ],
            // Cancelled
            [
                'room' => '305', 'guest_idx' => 7, 'status' => 'cancelled',
                'check_in' => now()->addDays(3)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
            ],
        ];

        foreach ($reservations as $data) {
            $room = $rooms->firstWhere('room_number', $data['room']);
            if (!$room || !isset($guests[$data['guest_idx']])) continue;

            $nights = now()->parse($data['check_in'])->diffInDays($data['check_out']);
            $total = $room->roomType->base_price * $nights;

            $reservation = Reservation::firstOrCreate(
                [
                    'room_id' => $room->id,
                    'check_in_date' => $data['check_in'],
                    'guest_id' => $guests[$data['guest_idx']]->id,
                ],
                [
                    'check_out_date' => $data['check_out'],
                    'status' => $data['status'],
                    'total_amount' => $total,
                    'adults' => rand(1, $room->roomType->max_occupancy),
                    'children' => 0,
                    'created_by' => $admin->id,
                ]
            );

            // Add room charge folio items for checked_in reservations
            if ($data['status'] === 'checked_in' && $reservation->folioItems()->count() === 0) {
                $checkIn = now()->parse($data['check_in']);
                for ($d = 0; $d < min($nights, 3); $d++) {
                    FolioItem::create([
                        'reservation_id' => $reservation->id,
                        'description' => 'Dhoma ' . $room->room_number . ' — nata ' . ($d + 1),
                        'amount' => $room->roomType->base_price,
                        'type' => 'room',
                        'charge_date' => $checkIn->copy()->addDays($d)->toDateString(),
                    ]);
                }
            }
        }
    }
}
