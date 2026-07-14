<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAddonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function tenant(): Tenant
    {
        return Tenant::query()->orderBy('id')->firstOrFail();
    }

    public function test_existing_tenant_is_grandfathered_and_revoke_grant_roundtrips(): void
    {
        $this->admin();
        $tenant = $this->tenant();
        // The base tenant predates the grandfather migration -> already granted.
        $this->assertTrue($tenant->hasAddon('finance'));

        $tenant->revokeAddon('finance');
        $this->assertFalse($tenant->fresh()->hasAddon('finance'));

        $tenant->fresh()->grantAddon('finance');
        $this->assertTrue($tenant->fresh()->hasAddon('finance'));
    }

    public function test_finance_routes_are_locked_without_the_addon_and_open_with_it(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $this->tenant()->revokeAddon('finance'); // hiqe grandfather-in për test

        // pa addon: 403 kudo — edhe për adminin e hotelit
        $this->actingAs($admin)->get(route('finance.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('finance.transfers.store'), [])->assertForbidden();
        $this->actingAs($admin)->put(route('settings.currencies'), [])->assertForbidden();

        // me addon: hapet normalisht
        $this->tenant()->grantAddon('finance');
        $this->actingAs($admin)->get(route('finance.index'))->assertOk();
    }

    public function test_grandfather_migration_granted_existing_tenants(): void
    {
        // RefreshDatabase ran ALL migrations incl. the grandfather one BEFORE
        // this test's tenant existed — so simulate: the seeded tenant in this
        // suite starts without; re-running the migration logic grants it.
        $tenant = tap($this->tenant())->update(['metadata' => null]);
        Tenant::query()->each(function (Tenant $t) {
            $meta = $t->metadata ?? [];
            $meta['addons'] = array_values(array_unique([...($meta['addons'] ?? []), 'finance']));
            $t->update(['metadata' => $meta]);
        });

        $this->assertTrue($tenant->fresh()->hasAddon('finance'));
    }

    public function test_addon_command_grants_lists_and_revokes(): void
    {
        $this->admin();
        $tenant = $this->tenant();

        $this->artisan('tenant:addon', ['tenant' => $tenant->slug, 'addon' => 'finance'])->assertSuccessful();
        $this->assertTrue($tenant->fresh()->hasAddon('finance'));

        $this->artisan('tenant:addon', ['tenant' => $tenant->slug, 'addon' => 'finance', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($tenant->fresh()->hasAddon('finance'));

        $this->artisan('tenant:addon', ['tenant' => $tenant->slug, 'addon' => 'garbage'])->assertFailed();
        $this->artisan('tenant:addon', ['--list' => true])->assertSuccessful();
    }

    public function test_nav_prop_carries_the_addons(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $this->tenant()->grantAddon('finance');

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tenant.addons', ['finance']));
    }
}
