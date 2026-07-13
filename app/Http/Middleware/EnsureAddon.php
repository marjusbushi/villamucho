<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Gates a paid add-on module (e.g. 'addon:finance'). The tenant's granted
 * add-ons live in tenants.metadata['addons'] — toggled by the platform
 * super-admin (tenant:addon command / control panel), never by hotel staff.
 */
class EnsureAddon
{
    public function handle(Request $request, Closure $next, string $addon)
    {
        $tenant = app(TenantContext::class)->tenant();

        if (! $tenant || ! $tenant->hasAddon($addon)) {
            abort(403, 'Ky modul është add-on i platformës dhe nuk është aktiv për këtë hotel. Kontakto administratorin e platformës për ta aktivizuar.');
        }

        return $next($request);
    }
}
