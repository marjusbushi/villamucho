<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_hero_text_per_language(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('settings.website'), [
            'hero_eyebrow_sq' => 'Ksamil', 'hero_eyebrow_en' => 'Ksamil EN',
            'hero_title_sq' => 'Titulli im', 'hero_title_en' => 'My title',
            'hero_subtitle_sq' => 'Nentitulli', 'hero_subtitle_en' => 'Subtitle',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals('Titulli im', Setting::get('hotel.hero_title_sq'));
        $this->assertEquals('My title', Setting::get('hotel.hero_title_en'));
        $this->assertEquals('Nentitulli', Setting::get('hotel.hero_subtitle_sq'));
    }
}
