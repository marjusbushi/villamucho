<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-set price guardrails per room type: the engine may suggest (and the
 * autopilot may apply) prices ONLY inside [min_price, max_price]. NULL = no
 * bound (falls back to the 0.25x-4x base_price sanity band on apply).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->nullable()->after('base_price');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });
    }
};
