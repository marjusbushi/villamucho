<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->unsignedInteger('covers')->nullable()->after('table_number')->comment('Number of diners on the order');
        });

        Schema::table('menu_categories', function (Blueprint $table) {
            $table->string('outlet')->nullable()->comment('Outlet for this category: bar or restaurant');
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pos_orders', 'covers')) {
                $table->dropColumn('covers');
            }
        });

        Schema::table('menu_categories', function (Blueprint $table) {
            if (Schema::hasColumn('menu_categories', 'outlet')) {
                $table->dropColumn('outlet');
            }
        });
    }
};
