<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $module = config('lora_modules.modules.housekeeping');

        DB::table('tenant_module_entitlements')
            ->where('module_code', 'housekeeping')
            ->update([
                'unit_price_cents' => 900,
                'pricing_snapshot' => json_encode($module, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $module = [
            'name' => 'Housekeeping',
            'description' => 'Pastrimi, checklistat dhe raportimi i problemeve.',
            'billing_model' => 'per_user',
            'unit_label' => 'përdorues',
            'unit_price_cents' => 900,
        ];

        DB::table('tenant_module_entitlements')
            ->where('module_code', 'housekeeping')
            ->update([
                'unit_price_cents' => 900,
                'pricing_snapshot' => json_encode($module, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);
    }
};
