<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class GuestProfileDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_profile_design_is_an_isolated_mockup_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('guests.profile-design'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Guests/ProfileDesign'));
    }
}
