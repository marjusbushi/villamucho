<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Finance becomes a PAID add-on (29 €/muaj). Existing tenants (Villa
     * Mucho) are grandfathered so nothing turns off on deploy — new tenants
     * start WITHOUT it until the platform admin grants it.
     */
    public function up(): void
    {
        Tenant::query()->each(function (Tenant $tenant) {
            $meta = $tenant->metadata ?? [];
            $meta['addons'] = array_values(array_unique([...($meta['addons'] ?? []), 'finance']));
            $tenant->timestamps = false;
            $tenant->update(['metadata' => $meta]);
        });
    }

    public function down(): void
    {
        // Grandfathering is a data grant — leaving it in place on rollback is safe.
    }
};
