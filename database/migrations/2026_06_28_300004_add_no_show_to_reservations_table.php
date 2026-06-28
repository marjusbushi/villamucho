<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Set when staff explicitly mark this reservation as a no-show.
            // A reservation is a confirmed no-show when no_show_at is not null.
            $table->dateTime('no_show_at')->nullable()->after('status');
            // The user who marked the no-show (nullable; survives user deletion).
            $table->foreignId('no_show_by')->nullable()->after('no_show_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'no_show_by')) {
                $table->dropConstrainedForeignId('no_show_by');
            }
            if (Schema::hasColumn('reservations', 'no_show_at')) {
                $table->dropColumn('no_show_at');
            }
        });
    }
};
