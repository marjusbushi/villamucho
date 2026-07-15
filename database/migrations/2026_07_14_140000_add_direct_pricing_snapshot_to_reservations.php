<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('rate_before_discount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('direct_discount_pct', 5, 2)->nullable()->after('rate_before_discount');
            $table->decimal('direct_discount_amount', 10, 2)->nullable()->after('direct_discount_pct');
        });

        $now = now();
        // Existing hotels receive the requested 10% direct benefit on deploy.
        // Brand-new tenants (no settings yet) keep the safe disabled default
        // until they explicitly configure it during onboarding.
        foreach (DB::table('settings')->whereNotNull('tenant_id')->where('group', '!=', 'pricing')->distinct()->pluck('tenant_id') as $tenantId) {
            DB::table('settings')->updateOrInsert(
                ['tenant_id' => $tenantId, 'group' => 'pricing_programs', 'key' => 'direct_discount_enabled'],
                ['value' => '1', 'type' => 'boolean', 'created_at' => $now, 'updated_at' => $now],
            );
            DB::table('settings')->updateOrInsert(
                ['tenant_id' => $tenantId, 'group' => 'pricing_programs', 'key' => 'direct_discount_pct'],
                ['value' => '10', 'type' => 'number', 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('group', 'pricing_programs')
            ->whereIn('key', ['direct_discount_enabled', 'direct_discount_pct'])
            ->delete();

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['rate_before_discount', 'direct_discount_pct', 'direct_discount_amount']);
        });
    }
};
