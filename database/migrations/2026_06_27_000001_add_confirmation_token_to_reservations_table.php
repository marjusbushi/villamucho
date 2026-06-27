<?php

use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add an unguessable confirmation token so the public booking-confirmation
     * page can be looked up by token instead of the enumerable sequential id (IDOR fix).
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->unique()->after('id');
        });

        // Backfill existing reservations with a token.
        Reservation::whereNull('confirmation_token')->get()->each(function (Reservation $reservation) {
            $reservation->confirmation_token = (string) Str::random(40);
            $reservation->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['confirmation_token']);
            $table->dropColumn('confirmation_token');
        });
    }
};
