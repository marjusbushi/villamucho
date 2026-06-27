<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * The production deploy runs `migrate --force` but NOT the seeders, so the new
     * shift permissions must be created + granted here or the POS would lock out
     * (no permission → nobody can open a shift → ordering is blocked). Additive
     * (givePermissionTo, not sync) so existing prod role permissions are untouched.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = ['open_pos_shift', 'close_pos_shift', 'close_any_pos_shift'];
        foreach ($perms as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $grant = function (string $role, array $names) {
            $r = Role::where('name', $role)->where('guard_name', 'web')->first();
            if ($r) {
                $r->givePermissionTo($names);
            }
        };

        $grant('admin', $perms);
        $grant('manager', $perms);
        $grant('receptionist', ['open_pos_shift', 'close_pos_shift']);
        $grant('pos_staff', ['open_pos_shift', 'close_pos_shift']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['open_pos_shift', 'close_pos_shift', 'close_any_pos_shift'] as $name) {
            Permission::where('name', $name)->where('guard_name', 'web')->delete();
        }
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
