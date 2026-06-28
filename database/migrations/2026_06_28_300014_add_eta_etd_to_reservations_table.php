<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('eta')->nullable()->comment('Expected time of arrival, HH:MM');
            $table->string('etd')->nullable()->comment('Expected time of departure, HH:MM');
            $table->boolean('early_check_in')->default(false)->nullable();
            $table->boolean('late_check_out')->default(false)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'eta')) {
                $table->dropColumn('eta');
            }
            if (Schema::hasColumn('reservations', 'etd')) {
                $table->dropColumn('etd');
            }
            if (Schema::hasColumn('reservations', 'early_check_in')) {
                $table->dropColumn('early_check_in');
            }
            if (Schema::hasColumn('reservations', 'late_check_out')) {
                $table->dropColumn('late_check_out');
            }
        });
    }
};
