<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $single = RoomType::firstOrCreate(
            ['name' => 'Single'],
            [
                'description' => 'Dhome per 1 person me shtrat tek, banjo private.',
                'base_price' => 50.00,
                'max_occupancy' => 1,
                'amenities' => ['WiFi', 'TV', 'Aire kondicionuar', 'Banjo private'],
            ]
        );

        $double = RoomType::firstOrCreate(
            ['name' => 'Double'],
            [
                'description' => 'Dhome per 2 persona me shtrat dopio, banjo private, ballkon.',
                'base_price' => 80.00,
                'max_occupancy' => 2,
                'amenities' => ['WiFi', 'TV', 'Aire kondicionuar', 'Banjo private', 'Ballkon', 'Minibar'],
            ]
        );

        $twin = RoomType::firstOrCreate(
            ['name' => 'Twin'],
            [
                'description' => 'Dhome me 2 shtretër teke, ideale per miq ose kolege.',
                'base_price' => 75.00,
                'max_occupancy' => 2,
                'amenities' => ['WiFi', 'TV', 'Aire kondicionuar', 'Banjo private'],
            ]
        );

        $suite = RoomType::firstOrCreate(
            ['name' => 'Suite'],
            [
                'description' => 'Suite luksoze me dhome ndenje te ndare, shtrat king-size, pamje nga deti.',
                'base_price' => 150.00,
                'max_occupancy' => 3,
                'amenities' => ['WiFi', 'TV 55"', 'Aire kondicionuar', 'Banjo luksoze', 'Ballkon', 'Minibar', 'Makineri kafeje', 'Pamje nga deti'],
            ]
        );

        $family = RoomType::firstOrCreate(
            ['name' => 'Family'],
            [
                'description' => 'Dhome familjare me shtrat dopio + shtrat tek, hapesire e madhe.',
                'base_price' => 120.00,
                'max_occupancy' => 4,
                'amenities' => ['WiFi', 'TV', 'Aire kondicionuar', 'Banjo private', 'Ballkon', 'Minibar', 'Shtrat shtese'],
            ]
        );

        // 15 rooms across 3 floors
        $rooms = [
            // Kati 1 — Single + Double
            ['room_type_id' => $single->id, 'room_number' => '101', 'floor' => 1, 'status' => 'available'],
            ['room_type_id' => $single->id, 'room_number' => '102', 'floor' => 1, 'status' => 'available'],
            ['room_type_id' => $double->id, 'room_number' => '103', 'floor' => 1, 'status' => 'occupied'],
            ['room_type_id' => $double->id, 'room_number' => '104', 'floor' => 1, 'status' => 'available'],
            ['room_type_id' => $twin->id,   'room_number' => '105', 'floor' => 1, 'status' => 'cleaning'],
            // Kati 2 — Double + Twin + Suite
            ['room_type_id' => $double->id, 'room_number' => '201', 'floor' => 2, 'status' => 'available'],
            ['room_type_id' => $double->id, 'room_number' => '202', 'floor' => 2, 'status' => 'occupied'],
            ['room_type_id' => $twin->id,   'room_number' => '203', 'floor' => 2, 'status' => 'available'],
            ['room_type_id' => $suite->id,  'room_number' => '204', 'floor' => 2, 'status' => 'occupied'],
            ['room_type_id' => $suite->id,  'room_number' => '205', 'floor' => 2, 'status' => 'maintenance'],
            // Kati 3 — Family + Suite
            ['room_type_id' => $family->id, 'room_number' => '301', 'floor' => 3, 'status' => 'available'],
            ['room_type_id' => $family->id, 'room_number' => '302', 'floor' => 3, 'status' => 'available'],
            ['room_type_id' => $suite->id,  'room_number' => '303', 'floor' => 3, 'status' => 'cleaning'],
            ['room_type_id' => $double->id, 'room_number' => '304', 'floor' => 3, 'status' => 'available'],
            ['room_type_id' => $single->id, 'room_number' => '305', 'floor' => 3, 'status' => 'available'],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['room_number' => $room['room_number']], $room);
        }
    }
}
