<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Channel-manager / OTA fields on a reservation, so an imported or webhook-
     * delivered booking can be matched back to its source (Booking.com etc.) and
     * never duplicated. channel_ref = the OTA booking number (e.g. Booking.com
     * "Book Number"); manual reservations leave these null.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('channel')->nullable()->after('status');        // booking.com | airbnb | expedia | direct
            $table->string('channel_ref')->nullable()->after('channel');   // OTA booking number
            $table->decimal('commission_amount', 10, 2)->nullable()->after('total_amount');
            $table->index(['channel', 'channel_ref']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['channel', 'channel_ref']);
            $table->dropColumn(['channel', 'channel_ref', 'commission_amount']);
        });
    }
};
