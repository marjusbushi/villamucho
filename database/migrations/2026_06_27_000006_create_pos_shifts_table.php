<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A POS "shift" (turn) is a per-user cash-drawer session: the cashier opens it
     * with a starting float, rings orders against it, then closes it with a counted
     * cash declaration. The closing totals are frozen here so a sealed Z-report can
     * never be retroactively changed by later order edits.
     */
    public function up(): void
    {
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            // Owner of the shift; restrictOnDelete keeps the Z-report trail intact.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open');

            $table->decimal('opening_float', 10, 2)->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Frozen-at-close reconciliation figures.
            $table->decimal('expected_cash', 10, 2)->nullable();
            $table->decimal('counted_cash', 10, 2)->nullable();
            $table->decimal('over_short', 10, 2)->nullable();

            // Frozen-at-close sales snapshot (by method). Cash drives the drawer;
            // card + room_charge are reported but excluded from the drawer math.
            $table->decimal('cash_sales', 10, 2)->default(0);
            $table->decimal('card_sales', 10, 2)->default(0);
            $table->decimal('room_charge_sales', 10, 2)->default(0);
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('cancelled_count')->default(0);

            $table->text('closing_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};
