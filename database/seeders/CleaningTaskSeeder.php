<?php

namespace Database\Seeders;

use App\Models\CleaningTask;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class CleaningTaskSeeder extends Seeder
{
    public function run(): void
    {
        $housekeeper = User::where('email', 'housekeeping@chanelmanager.com')->first();

        // Tasks for rooms in cleaning status
        $cleaningRooms = Room::where('status', 'cleaning')->get();
        foreach ($cleaningRooms as $room) {
            CleaningTask::firstOrCreate(
                ['room_id' => $room->id, 'status' => 'pending'],
                [
                    'assigned_to' => $housekeeper?->id,
                    'type' => 'checkout_clean',
                    'priority' => 'urgent',
                    'notes' => 'Pastrim pas check-out',
                ]
            );
        }

        // Some completed tasks for history
        $availableRooms = Room::where('status', 'available')->limit(3)->get();
        foreach ($availableRooms as $i => $room) {
            CleaningTask::firstOrCreate(
                ['room_id' => $room->id, 'status' => 'completed'],
                [
                    'assigned_to' => $housekeeper?->id,
                    'type' => $i === 0 ? 'deep_clean' : 'stayover_clean',
                    'priority' => 'normal',
                    'completed_at' => now()->subHours(rand(2, 8)),
                ]
            );
        }

        // One in-progress task
        $occupiedRoom = Room::where('status', 'occupied')->first();
        if ($occupiedRoom) {
            CleaningTask::firstOrCreate(
                ['room_id' => $occupiedRoom->id, 'type' => 'stayover_clean'],
                [
                    'assigned_to' => $housekeeper?->id,
                    'status' => 'in_progress',
                    'priority' => 'normal',
                    'notes' => 'Pastrim ditor — mysafiri largohet ne 14:00',
                ]
            );
        }
    }
}
