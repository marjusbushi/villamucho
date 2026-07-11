<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\TenantRoleService;
use App\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = app(TenantContext::class)->idOrDefault();
        $tenant = $tenantId ? Tenant::query()->find($tenantId) : null;

        if ($tenant) {
            app(TenantRoleService::class)->provision($tenant);
        }
    }
}
