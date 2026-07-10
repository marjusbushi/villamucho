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
            $table->string('created_via', 24)->default('staff')->after('created_by');
        });

        $systemUserIds = DB::table('users')
            ->where('email', 'system@villamucho.local')
            ->pluck('id');

        // Preserve provenance before merging legacy Manual/empty into Direct.
        // A channel reference is the durable marker for historical OTA imports,
        // including old runs that fell back to the first admin as their creator.
        DB::table('reservations')
            ->whereNotNull('channel_ref')
            ->where('channel_ref', '!=', '')
            ->whereNotNull('channel')
            ->whereNotIn('channel', ['', 'manual', 'direct'])
            ->update(['created_via' => 'channel_manager']);

        if ($systemUserIds->isNotEmpty()) {
            DB::table('reservations')
                ->whereNotNull('channel')
                ->whereNotIn('channel', ['', 'manual', 'direct'])
                ->whereIn('created_by', $systemUserIds)
                ->update(['created_via' => 'channel_manager']);

            DB::table('reservations')
                ->where('channel', 'direct')
                ->whereIn('created_by', $systemUserIds)
                ->update(['created_via' => 'website']);
        }

        DB::table('reservations')
            ->whereNull('channel')
            ->orWhereIn('channel', ['', 'manual'])
            ->update(['channel' => 'direct']);
    }

    public function down(): void
    {
        // Restore the old semantic split before the provenance column disappears.
        DB::table('reservations')
            ->where('channel', 'direct')
            ->where('created_via', 'staff')
            ->update(['channel' => 'manual']);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('created_via');
        });
    }
};
