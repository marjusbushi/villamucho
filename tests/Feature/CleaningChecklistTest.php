<?php

namespace Tests\Feature;

use App\Models\CleaningTask;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleaningChecklistTest extends TestCase
{
    use RefreshDatabase;

    private function housekeeper(): User
    {
        $u = User::factory()->create();
        $u->assignRole('housekeeping');

        return $u;
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    private function room(string $status = 'cleaning'): Room
    {
        $type = RoomType::create(['name' => 'Std', 'base_price' => 100, 'max_occupancy' => 3, 'amenities' => []]);

        return Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'status' => $status]);
    }

    private function task(array $attrs = []): CleaningTask
    {
        return CleaningTask::create(array_merge([
            'room_id' => $this->room()->id,
            'type' => 'checkout_clean',
            'status' => 'pending',
            'priority' => 'normal',
        ], $attrs));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        // Deterministic 2-item template for checkout_clean.
        Setting::set('housekeeping.checklists', ['checkout_clean' => ['Cars', 'Peshqire']], 'json');
    }

    public function test_starting_a_task_snapshots_the_template_and_stamps_started(): void
    {
        $hk = $this->housekeeper();
        $t = $this->task(['assigned_to' => $hk->id]);

        $this->actingAs($hk)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'in_progress'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $t->refresh();
        $this->assertSame('in_progress', $t->status);
        $this->assertEquals(
            [
                ['label' => 'Cars', 'done' => false, 'done_at' => null],
                ['label' => 'Peshqire', 'done' => false, 'done_at' => null],
            ],
            $t->checklist,
        );
        $this->assertNotNull($t->started_at);
        $this->assertSame($hk->id, $t->started_by);
    }

    public function test_completing_is_blocked_until_every_item_is_done(): void
    {
        $hk = $this->housekeeper();
        $t = $this->task([
            'assigned_to' => $hk->id,
            'status' => 'in_progress',
            'checklist' => [
                ['label' => 'Cars', 'done' => true, 'done_at' => now()->toDateTimeString()],
                ['label' => 'Peshqire', 'done' => false, 'done_at' => null],
            ],
        ]);

        // One item undone → rejected, stays in_progress, room still cleaning.
        $this->actingAs($hk)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'completed'])
            ->assertSessionHas('error');

        $this->assertSame('in_progress', $t->fresh()->status);
        $this->assertSame('cleaning', $t->fresh()->room->status);

        // All done → completed, room freed.
        $t->update(['checklist' => [
            ['label' => 'Cars', 'done' => true, 'done_at' => now()->toDateTimeString()],
            ['label' => 'Peshqire', 'done' => true, 'done_at' => now()->toDateTimeString()],
        ]]);

        $this->actingAs($hk)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $t->fresh()->status);
        $this->assertSame('available', $t->fresh()->room->status);
    }

    public function test_checklist_endpoint_persists_done_flags(): void
    {
        $hk = $this->housekeeper();
        $t = $this->task([
            'assigned_to' => $hk->id,
            'status' => 'in_progress',
            'checklist' => [
                ['label' => 'Cars', 'done' => false, 'done_at' => null],
                ['label' => 'Peshqire', 'done' => false, 'done_at' => null],
            ],
        ]);

        $this->actingAs($hk)
            ->patch(route('housekeeping.checklist', $t->id), ['items' => [
                ['label' => 'Cars', 'done' => true],
                ['label' => 'Peshqire', 'done' => false],
            ]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $list = $t->fresh()->checklist;
        $this->assertTrue($list[0]['done']);
        $this->assertNotNull($list[0]['done_at']);
        $this->assertFalse($list[1]['done']);
        $this->assertNull($list[1]['done_at']);
    }

    public function test_inspecting_records_who_and_when(): void
    {
        $admin = $this->admin();
        $t = $this->task(['status' => 'completed']);

        $this->actingAs($admin)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'inspected'])
            ->assertSessionHasNoErrors();

        $t->refresh();
        $this->assertSame('inspected', $t->status);
        $this->assertSame($admin->id, $t->inspected_by);
        $this->assertNotNull($t->inspected_at);
    }

    public function test_editing_the_template_does_not_change_a_started_task(): void
    {
        $hk = $this->housekeeper();
        $t = $this->task(['assigned_to' => $hk->id]);

        $this->actingAs($hk)->patch(route('housekeeping.status', $t->id), ['status' => 'in_progress']);
        $snapshot = $t->fresh()->checklist;

        // Admin rewrites the template afterwards.
        Setting::set('housekeeping.checklists', ['checkout_clean' => ['Krejt', 'Tjeter']], 'json');

        $this->assertEquals($snapshot, $t->fresh()->checklist);
        $this->assertSame('Cars', $t->fresh()->checklist[0]['label']);
    }

    public function test_a_type_without_a_template_completes_immediately(): void
    {
        $hk = $this->housekeeper();
        // 'inspection' has no default template and no override key → empty checklist.
        $t = $this->task(['assigned_to' => $hk->id, 'type' => 'inspection']);

        $this->actingAs($hk)->patch(route('housekeeping.status', $t->id), ['status' => 'in_progress']);
        $this->assertSame([], $t->fresh()->checklist);

        $this->actingAs($hk)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $t->fresh()->status);
    }

    public function test_completing_or_inspecting_a_task_whose_room_was_deleted_does_not_500(): void
    {
        $hk = $this->housekeeper();
        $room = $this->room('cleaning');
        $t = CleaningTask::create([
            'room_id' => $room->id,
            'type' => 'inspection', // empty template → completes immediately
            'status' => 'in_progress',
            'assigned_to' => $hk->id,
            'checklist' => [],
        ]);

        // The room disappears from under the task (mistake #93 — null-guard $task->room).
        $room->delete();
        $this->assertNull($t->fresh()->room);

        $this->actingAs($hk)
            ->patch(route('housekeeping.status', $t->id), ['status' => 'completed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $t->fresh()->status);

        $this->actingAs($this->admin())
            ->patch(route('housekeeping.status', $t->id), ['status' => 'inspected'])
            ->assertSessionHasNoErrors();
        $this->assertSame('inspected', $t->fresh()->status);
    }

    public function test_settings_persists_and_exposes_sanitized_checklists(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('settings.housekeeping'), [
                'task_types' => ['checkout_clean', 'deep_clean'],
                'auto_create_on_checkout' => true,
                'default_priority' => 'normal',
                'checklists' => [
                    'checkout_clean' => ['Nje', '   ', 'Dy'], // blank must be dropped, items trimmed
                    'deep_clean' => ['Thelle'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $saved = Setting::get('housekeeping.checklists');
        $this->assertSame(['Nje', 'Dy'], $saved['checkout_clean']);
        $this->assertSame(['Thelle'], $saved['deep_clean']);

        // index() exposes it to the settings page under settings.housekeeping.checklists.
        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('settings.housekeeping.checklists.deep_clean.0', 'Thelle'));
    }

    public function test_non_owner_housekeeper_cannot_open_or_edit_another_persons_task(): void
    {
        $hk1 = $this->housekeeper();
        $hk2 = $this->housekeeper();
        $t = $this->task([
            'assigned_to' => $hk1->id,
            'status' => 'in_progress',
            'checklist' => [['label' => 'Cars', 'done' => false, 'done_at' => null]],
        ]);

        // Someone else's task → 403 on both the clean view and the checklist write.
        $this->actingAs($hk2)->get(route('housekeeping.clean', $t->id))->assertForbidden();
        $this->actingAs($hk2)
            ->patch(route('housekeeping.checklist', $t->id), ['items' => [['label' => 'Cars', 'done' => true]]])
            ->assertForbidden();

        // The assigned housekeeper and a supervisor (admin) may open it.
        $this->actingAs($hk1)->get(route('housekeeping.clean', $t->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Housekeeping/Clean')
                ->where('task.assigned_to', $hk1->name)
                ->where('task.started_by', null)
                ->where('task.room.room_number', '101'));
        $this->actingAs($this->admin())->get(route('housekeeping.clean', $t->id))->assertOk();
    }
}
