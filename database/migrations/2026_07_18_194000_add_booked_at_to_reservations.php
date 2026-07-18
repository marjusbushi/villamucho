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
            $table->timestamp('booked_at')->nullable()->after('created_via');
        });

        DB::table('reservations')
            ->whereNull('booked_at')
            ->update(['booked_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('booked_at');
        });
    }
};
