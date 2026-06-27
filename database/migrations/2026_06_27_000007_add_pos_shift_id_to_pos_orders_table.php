<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bind each POS order to the shift that rang it. Nullable so existing orders
     * stay NULL (legacy / pre-shift) without a data migration; nullOnDelete so
     * deleting a shift never deletes its orders. Composite index serves the hot
     * "open orders in this shift" + shift-total aggregation queries.
     */
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->foreignId('pos_shift_id')->nullable()->after('created_by')
                ->constrained('pos_shifts')->nullOnDelete();
            $table->index(['pos_shift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropIndex(['pos_shift_id', 'status']);
            $table->dropConstrainedForeignId('pos_shift_id');
        });
    }
};
