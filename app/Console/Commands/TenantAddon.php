<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Platform-side add-on management (until the control panel toggle lands):
 *
 *   php artisan tenant:addon villa-mucho finance          # grant
 *   php artisan tenant:addon villa-mucho finance --revoke # revoke
 *   php artisan tenant:addon --list                       # catalog + who has what
 */
class TenantAddon extends Command
{
    protected $signature = 'tenant:addon {tenant? : Tenant slug or id} {addon? : Addon key} {--revoke} {--list}';

    protected $description = 'Grant/revoke a paid add-on for a tenant (platform super-admin)';

    public function handle(): int
    {
        if ($this->option('list') || ! $this->argument('tenant')) {
            $this->table(['Addon', 'Çmimi', 'Përshkrimi'], collect(Tenant::ADDONS)
                ->map(fn ($a, $k) => [$k, $a['price_eur'].' €/'.$a['period'], $a['description']])->values());
            foreach (Tenant::query()->get() as $t) {
                $this->line(sprintf('  %-24s %s', $t->slug, $t->addons() ? implode(', ', $t->addons()) : '—'));
            }

            return self::SUCCESS;
        }

        $key = (string) $this->argument('addon');
        if (! isset(Tenant::ADDONS[$key])) {
            $this->error("Addon i panjohur \"{$key}\" — të njohurit: ".implode(', ', array_keys(Tenant::ADDONS)));

            return self::FAILURE;
        }

        $ref = (string) $this->argument('tenant');
        $tenant = Tenant::where('slug', $ref)->orWhere('id', (int) $ref)->first();
        if (! $tenant) {
            $this->error("Tenant \"{$ref}\" nuk u gjet.");

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $tenant->revokeAddon($key);
            $this->info("'{$key}' u HOQ për {$tenant->name}.");
        } else {
            $tenant->grantAddon($key);
            $this->info("'{$key}' u AKTIVIZUA për {$tenant->name} (".Tenant::ADDONS[$key]['price_eur'].' €/'.Tenant::ADDONS[$key]['period'].').');
        }

        return self::SUCCESS;
    }
}
