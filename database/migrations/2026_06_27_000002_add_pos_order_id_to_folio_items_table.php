<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a folio line back to the POS order that produced it, so a room
     * charge is traceable and reversible (no more orphan, unlinked charges).
     */
    public function up(): void
    {
        Schema::table('folio_items', function (Blueprint $table) {
            $table->foreignId('pos_order_id')->nullable()->after('reservation_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('folio_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pos_order_id');
        });
    }
};
