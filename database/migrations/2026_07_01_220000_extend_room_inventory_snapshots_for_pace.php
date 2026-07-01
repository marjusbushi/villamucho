<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repurpose room_inventory_snapshots (created 2026_06_28_300013 but never
 * written to — zero writers, empty on every environment) from one-row-per-day
 * whole-property counts into per (snapshot_date × stay_date × room_type)
 * on-the-books rows. This is the raw feed for the pickup-pace pricing factor:
 * "how many rooms were booked for Aug 15 as seen 30 days out vs 7 days out".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_inventory_snapshots', function (Blueprint $table) {
            $table->dropUnique(['snapshot_date']);
        });

        Schema::table('room_inventory_snapshots', function (Blueprint $table) {
            $table->date('stay_date')->after('snapshot_date');
            $table->foreignId('room_type_id')->after('stay_date')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('booked')->default(0)->after('out_of_order');

            $table->unique(['snapshot_date', 'stay_date', 'room_type_id'], 'ris_snapshot_stay_type_unique');
            $table->index('stay_date');
        });
    }

    public function down(): void
    {
        Schema::table('room_inventory_snapshots', function (Blueprint $table) {
            $table->dropUnique('ris_snapshot_stay_type_unique');
            $table->dropIndex(['stay_date']);
            $table->dropConstrainedForeignId('room_type_id');
            $table->dropColumn(['stay_date', 'booked']);
        });

        Schema::table('room_inventory_snapshots', function (Blueprint $table) {
            $table->unique('snapshot_date');
        });
    }
};
