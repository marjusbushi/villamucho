<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->boolean('pos_salesperson_enabled')->default(true);
            $table->string('pos_pin_hash')->nullable();
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('pos_orders')->whereNull('salesperson_id')->update([
            'salesperson_id' => DB::raw('created_by'),
        ]);
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_id');
            $table->dropConstrainedForeignId('salesperson_id');
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropColumn(['pos_salesperson_enabled', 'pos_pin_hash']);
        });
    }
};
