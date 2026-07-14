<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $module = config('lora_modules.modules.finance');

        if (! is_array($module)) {
            return;
        }

        $now = now();

        DB::table('tenant_subscriptions')
            ->orderBy('tenant_id')
            ->each(function (object $subscription) use ($module, $now): void {
                DB::table('tenant_module_entitlements')->updateOrInsert(
                    [
                        'tenant_id' => $subscription->tenant_id,
                        'module_code' => 'finance',
                    ],
                    [
                        'enabled' => false,
                        'quantity' => 1,
                        'unit_price_cents' => $module['unit_price_cents'],
                        'percentage_bps' => null,
                        'pricing_snapshot' => json_encode($module, JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $tenant = DB::table('tenants')->find($subscription->tenant_id);
                $metadata = json_decode((string) ($tenant?->metadata ?? '{}'), true);
                $metadata = is_array($metadata) ? $metadata : [];
                $metadata['billing_access']['modules']['finance'] = false;

                DB::table('tenants')->where('id', $subscription->tenant_id)->update([
                    'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('tenant_module_entitlements')
            ->where('module_code', 'finance')
            ->delete();

        DB::table('tenants')->orderBy('id')->each(function (object $tenant): void {
            $metadata = json_decode((string) ($tenant->metadata ?? '{}'), true);
            $metadata = is_array($metadata) ? $metadata : [];
            unset($metadata['billing_access']['modules']['finance']);

            DB::table('tenants')->where('id', $tenant->id)->update([
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            ]);
        });
    }
};
