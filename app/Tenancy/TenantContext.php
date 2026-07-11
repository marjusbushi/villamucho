<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Closure;
use Spatie\Permission\PermissionRegistrar;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->getKey());
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    public function idOrDefault(): ?int
    {
        return $this->id() ?? (app()->runningInConsole()
            ? Tenant::query()->active()->orderBy('id')->value('id')
            : null);
    }

    public function clear(): void
    {
        $this->set(null);
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->set($tenant);

        try {
            return $callback($tenant);
        } finally {
            $this->set($previous);
        }
    }
}
