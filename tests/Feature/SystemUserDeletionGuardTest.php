<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The system booking user (system@villamucho.local) is what every public/website
 * booking is attributed to. It was once soft-deleted from the admin Users screen,
 * which took the public booking funnel down for 11 days (duplicate-key on re-insert).
 * The admin must not be able to delete it.
 */
class SystemUserDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_cannot_soft_delete_the_system_booking_user(): void
    {
        $admin = $this->admin();
        $system = User::factory()->create([
            'email' => 'system@villamucho.local',
            'name' => 'Website Booking',
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $system->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        // Untouched — the booking funnel depends on it existing.
        $this->assertNotSoftDeleted('users', ['id' => $system->id]);
    }

    public function test_admin_can_still_soft_delete_a_normal_user(): void
    {
        $admin = $this->admin();
        $victim = User::factory()->create(['email' => 'someone@example.com']);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $victim->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $victim->id]);
    }
}
