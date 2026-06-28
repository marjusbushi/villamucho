<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dateTime('paid_at')->nullable()->after('status');
            $table->date('business_date')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pos_orders', 'business_date')) {
                $table->dropColumn('business_date');
            }
            if (Schema::hasColumn('pos_orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
