<?php

namespace Tests\Feature;

use App\Models\CleaningTask;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Regression: a cleaning task can outlive its room (rooms were deleted on prod while
 * tasks still referenced them — the restrictOnDelete FK was not enforced, orphaning 4
 * tasks). Marking such a task completed/inspected, or reporting a maintenance issue on
 * it, dereferenced $cleaningTask->room (null) and 500'd the whole housekeeping board.
 */
class CleaningTaskOrphanRoomTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: CleaningTask} — an admin + a task whose room was deleted */
    private function orphanTaskSetup(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $type = RoomType::create(['name' => 'Std', 'base_price' => 100, 'max_occupancy' => 3, 'amenities' => []]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'status' => 'cleaning']);
        $task = CleaningTask::create([
            'room_id' => $room->id,
            'type' => 'checkout_clean',
            'status' => 'in_progress',
            'priority' => 'normal',
        ]);

        // Reproduce the prod state: the room is gone but the task still points at it.
        Schema::disableForeignKeyConstraints();
        $room->delete();
        Schema::enableForeignKeyConstraints();

        return [$admin, $task];
    }

    public function test_completing_a_task_whose_room_was_deleted_does_not_500(): void
    {
        [$admin, $task] = $this->orphanTaskSetup();

        $this->actingAs($admin)
            ->patch(route('housekeeping.status', $task->id), ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_reporting_a_maintenance_issue_on_an_orphan_room_task_does_not_500(): void
    {
        [$admin, $task] = $this->orphanTaskSetup();

        $this->actingAs($admin)
            ->post(route('housekeeping.issue', $task->id), [
                'issue_reported' => 'Dritarja e thyer',
                'set_maintenance' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Dritarja e thyer', $task->fresh()->issue_reported);
    }
}
