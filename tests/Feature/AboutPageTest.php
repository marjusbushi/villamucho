<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_about_texts_and_photos(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('settings.about'), [
            'hero_title_sq' => 'Rreth Villa Mucho',
            'hero_title_en' => 'About Villa Mucho',
            'story_title_sq' => 'Historia jonë',
            'story_p1_sq' => 'Paragrafi i parë.',
            'stat1_value' => '18+',
            'stat1_label_sq' => 'Dhoma',
            'stat1_label_en' => 'Rooms',
            'hero_image' => UploadedFile::fake()->create('hero.jpg', 200, 'image/jpeg'),
            'story_image' => UploadedFile::fake()->create('story.jpg', 200, 'image/jpeg'),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $this->assertEquals('Rreth Villa Mucho', Setting::get('about.hero_title_sq'));
        $this->assertEquals('About Villa Mucho', Setting::get('about.hero_title_en'));
        $this->assertEquals('Historia jonë', Setting::get('about.story_title_sq'));
        $this->assertEquals('18+', Setting::get('about.stat1_value'));

        $heroPath = Setting::get('about.hero_image');
        $this->assertNotNull($heroPath);
        Storage::disk('public')->assertExists($heroPath);
        Storage::disk('public')->assertExists(Setting::get('about.story_image'));
    }

    public function test_replacing_a_photo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('settings.about'), [
            'hero_image' => UploadedFile::fake()->create('first.jpg', 100, 'image/jpeg'),
        ]);
        $first = Setting::get('about.hero_image');

        $this->actingAs($admin)->post(route('settings.about'), [
            'hero_image' => UploadedFile::fake()->create('second.jpg', 100, 'image/jpeg'),
        ]);
        $second = Setting::get('about.hero_image');

        $this->assertNotEquals($first, $second);
        Storage::disk('public')->assertMissing($first);   // old file removed
        Storage::disk('public')->assertExists($second);
    }

    public function test_non_admin_cannot_update_about(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(); // no admin role

        $this->actingAs($user)
            ->post(route('settings.about'), ['hero_title_sq' => 'Hack'])
            ->assertForbidden();

        $this->assertNull(Setting::get('about.hero_title_sq'));
    }

    public function test_about_page_exposes_saved_content_to_the_public(): void
    {
        Setting::set('about.story_title_sq', 'Historia e Villa Mucho');
        Setting::set('about.stat1_value', '22+');

        $this->get(route('website.about'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Website/About')
                ->where('about.story_title_sq', 'Historia e Villa Mucho')
                ->where('about.stat1_value', '22+')
            );
    }
}
